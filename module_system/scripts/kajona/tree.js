/**
 * (c) 2013-2017 by Kajona, www.kajona.de
 * Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt
 */

/**
 * @module tree
 */
define('tree', ['jquery', 'jstree', 'ajax', 'lang', 'cacheManager'], function ($, jstree, ajax, lang, cacheManager) {

    /** @exports tree */
    var kajonatree = {
        helper: {},
        contextmenu: {},
        conditionalselect: {}
    };

    /**
     * Object to initialize a JsTree
     */
    kajonatree.jstree = function () {

        var treeContext = this;

        this.loadNodeDataUrl = null;
        this.rootNodeSystemid = null;
        this.treeConfig = null;//@see class \Kajona\System\System\SystemJSTreeConfig for structure
        this.treeId = null;
        this.treeviewExpanders = null; //array of ids
        this.initiallySelectedNodes = null; //array of ids

        /**
         * Moves nodes below another node.
         * Triggers a reload of the page after node was moved
         *
         * @param data
         * @returns {boolean}
         */
        function moveNode(data) {
            //node data
            var strNodeId = data.node.id,
                strNewParentId = data.parent,
                strOldParentId = data.old_parent,
                intNewPostiton = data.position,
                intOldPostiton = data.old_position;

            /* Get table row which should be moved*/
            var $objTableRowMoved = $('tr[data-systemid=' + strNodeId + ']').closest('tbody');

            //same parent
            if (strNewParentId == strOldParentId) {

                /* Move table row to according position*/
                if ($objTableRowMoved.length > 0) {
                    var arrElementsInTable = $objTableRowMoved.closest('table').find('tbody');

                    if (intOldPostiton > intNewPostiton) {
                        $(arrElementsInTable[intOldPostiton]).insertBefore($(arrElementsInTable[intNewPostiton]));
                    }
                    if (intOldPostiton < intNewPostiton) {
                        $(arrElementsInTable[intOldPostiton]).insertAfter($(arrElementsInTable[intNewPostiton]));
                    }
                }

                /* Call server*/
                ajax.genericAjaxCall("system", "setAbsolutePosition", strNodeId + "&listPos=" + (intNewPostiton + 1), function (data, status, jqXHR) {
                    ajax.regularCallback(data, status, jqXHR);
                });
            }
            //different parent
            else if (strNewParentId != strOldParentId) {

                /* hide table row*/
                if ($objTableRowMoved.length > 0) {
                    $objTableRowMoved.hide();
                }

                /* Call server*/
                ajax.genericAjaxCall("system", "setPrevid", strNodeId + "&prevId=" + strNewParentId, function (data, status, jqXHR) {

                    if(status == 'success') {
                        ajax.genericAjaxCall("system", "setAbsolutePosition", strNodeId + "&listPos=" + (intNewPostiton + 1), function (data, status, jqXHR) {
                            ajax.regularCallback(data, status, jqXHR);
                        });
                    }
                    else {
                        ajax.regularCallback(data, status, jqXHR);
                    }
                });
            }

            return true;
        }

        /**
         * Checks if a node can be dropped to a certain place in the tree
         *
         * @param node - the dragged node
         * @param node_parent
         * @param node_position
         * @param more
         * @returns {boolean}
         */
        function checkMoveNode(node, node_parent, node_position, more) {
            var targetNode = more.ref,
                strDragId = node.id,
                strTargetId = targetNode.id,
                strInsertPosition = more.pos; //"b"=>before, "a"=>after, "i"=inside


            //1. user can only move node if he has right on the dragged node and the parent node
            if(!node.data.rightedit && !node_parent.data.rightedit) {
                return false;
            }

            //2. dragged node already direct childnode of target?
            var arrTargetChildren = targetNode.children;
            if ($.inArray(strDragId, arrTargetChildren) > -1) {
                return false;
            }

            //3. dragged node is parent of target?
            var arrTargetParents = targetNode.parents;
            if ($.inArray(strDragId, arrTargetParents) > -1) {
                return false;//TODO maybe not needed, already check by jstree it self
            }

            //4. dragged node same as target node?
            if (strDragId == strTargetId) {
                return false;//TODO maybe not needed, already check by jstree it self
            }

            //5. Check if node is valid child of node_parent
            if(!isValidChildNodeForParent(node, node_parent)) {
                return false;
            }

            //6. Check node_parent is valid parent for node
            if (!isValidParentNodeForChild(node, node_parent)) {
                return false;
            }

            return true;
        }


        /**
         * Checks if given node is a valid child node for the given parent
         *
         * @param node
         * @param node_parent
         * @returns {boolean}
         */
        function isValidChildNodeForParent(node, node_parent) {
            if(node.data.customtypes) {
                var curType = node.data.customtypes.type;
                var arrValidChildrenTargetParent = node_parent.data.customtypes.valid_children;

                if(arrValidChildrenTargetParent === null) {
                    return true;
                }

                //now check if the current type can be placed to the target node by checking the valid children
                if($.inArray(curType, arrValidChildrenTargetParent) === -1) {//-1 == curType not in array
                    return false;
                }
            }

            return true;
        }

        /*
         * Check node_parent is valid parent for node
         *
         * Determines if one of the parent nodes of the given node 'node' has check_parent_id_active set to true.
         *  If this is not the case, everything is ok -> return true
         *  If this is case it will checked, if the the new parent node 'node_parent' is somewhere within the path of the found node
         */
        function isValidParentNodeForChild(node, node_parent) {
            var nodeWithDataAttribute = getNodeWithDataAttribute(node, 'check_parent_id_active', true);
            if(nodeWithDataAttribute !== null) {
                var idToCheck = nodeWithDataAttribute.id;
                var arrParents = node_parent.parents;
                arrParents.unshift(node_parent.id);

                if ($.inArray(idToCheck, arrParents) === -1) {
                    return false;
                }
            }
            return true;
        }



        /**
         * Checks if the current node has the given data attribute.
         * If 'bitCheckParentNodesOnly' is set to true the first parent node which have the 'strAttribute' set will be returned.
         *
         * Returns the node which has the given data attribute or null
         *
         * @param node
         * @param strAttribute
         * @param bitCheckParentNodesOnly - set to true if only parant nodes should be checked
         * @returns Returns the node which has the given data attribute or null
         */
        function getNodeWithDataAttribute(node, strAttribute, bitCheckParentNodesOnly) {

            //Check parent nodes
            if(bitCheckParentNodesOnly === true) {
                var tree = kajonatree.helper.getTreeInstance();
                var arrParents = node.parents;

                for (var i = 0, len = arrParents.length; i < len; i++) {
                    var parentNode = tree.get_node(arrParents[i]);
                    if (parentNode.id == "#") {//skip internal root node
                        return null;
                    }
                    if (parentNode.data.hasOwnProperty(strAttribute)) {
                        return parentNode
                    }
                }
            }
            else {
                //Check node directly
                if(node.data.hasOwnProperty(strAttribute)){
                    return node;
                }
            }

            return null;
        }


        /**
         * Callback used for dragging elements from the list to the tree
         *
         * @param e
         * @returns {*}
         */
        this.listDnd = function (e) {
            var strSystemId = $(this).closest("tr").data("systemid");
            var strTitle = $(this).closest("tr").find(".title").text();

            //Check if there a jstree instance (there should only one)
            var jsTree = $.jstree.reference('#' + treeContext.treeId);

            //create basic node
            var objNode = {
                id: strSystemId,
                text: strTitle
            };

            //if a jstree instanse exists try to find a node for it
            if (jsTree != null) {
                var treeNode = jsTree.get_node(strSystemId);
                if (treeNode != false) {
                    objNode = treeNode;
                }
            }

            var objData = {
                'jstree': true,
                'obj': $(this),
                'nodes': [
                    objNode
                ]
            };
            var event = e;
            var strHtml = '<div id="jstree-dnd" class="jstree-default"><i class="jstree-icon jstree-er"></i>' + strTitle + '</div>';//drag container
            return $.vakata.dnd.start(event, objData, strHtml);
        };


        /**
         * Initializes the jstree
         */
        this.initTree = function () {

            /* Create config object*/
            var jsTreeObj = {
                'core': {
                    /**
                     *
                     * @param operation operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
                     * @param node the selected node
                     * @param node_parent
                     * @param node_position
                     * @param more on dnd => more is the hovered node
                     * @returns {boolean}
                     */
                    'check_callback': function (operation, node, node_parent, node_position, more) {
                        // operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
                        // in case of 'rename_node' node_position is filled with the new node name

                        var bitReturn = false;

                        if (operation === 'move_node') {
                            //check when dragging
                            bitReturn = true;
                            if (more.dnd) {
                                bitReturn = checkMoveNode(node, node_parent, node_position, more);
                            }
                        }

                        if (operation === 'create_node') {
                            bitReturn = true;//Check for assignment tree
                        }

                        return bitReturn;
                    },
                    'expand_selected_onload': true,//if left as true all parents of all selected nodes will be opened once the tree loads (so that all selected nodes are visible to the user)
                    'data': {
                        'url': function (node) {
                            return treeContext.loadNodeDataUrl;
                        },
                        'data': function (node, cb) {//params to be added to the given ulr on ajax call
                            var data = {};
                            if (node.id === "#") {
                                data.systemid = treeContext.rootNodeSystemid;
                                data.jstree_initialtoggling = treeContext.treeviewExpanders;
                            }
                            else {
                                data.systemid = node.id;

                                if(node.data) {
                                    data.jstree_loadallchildnodes = node.data.jstree_loadallchildnodes;
                                }
                            }

                            if(node.hasOwnProperty("data") && node.data.hasOwnProperty("loadall")) {
                                data.loadall = true;
                            }

                            return data;
                        }
                    },
                    'themes': {
                        "url": false,
                        "icons": false
                    },
                    'animation': false
                },
                'dnd': {
                    'check_while_dragging': true,
                    'is_draggable': function(arrArguments, event) {

                        var node = arrArguments[0];
                        var nodeDataAttribute = getNodeWithDataAttribute(node, 'is_not_draggable');
                        if(nodeDataAttribute !== null){
                            return false;
                        }

                        return true;
                    }
                },
                'checkbox': {},
                'types': {},
                'contextmenu': {},
                'conditionalselect': kajonatree.conditionalselect.handleConditionalSelect,

                'plugins': ['conditionalselect']
            };

            /* Extend Js Tree Object due to jsTreeConfig*/
            if (this.treeConfig.checkbox) {
                jsTreeObj.plugins.push('checkbox');
                jsTreeObj.checkbox.three_state = false;//disable three state checkboxes by default
            }
            if (this.treeConfig.dnd) {
                jsTreeObj.plugins.push('dnd');
            }
            if (this.treeConfig.types) {
                jsTreeObj.plugins.push('types');
                jsTreeObj.types = this.treeConfig.types;
            }
            if (this.treeConfig.contextmenu) {
                jsTreeObj.plugins.push('contextmenu');
                jsTreeObj.contextmenu.items = this.treeConfig.contextmenu.items;
                jsTreeObj.contextmenu.show_at_node = false;
            }

            /* Create the tree */
            var $jsTree = $('#' + this.treeId).jstree(jsTreeObj);

            /*Register events*/
            $jsTree
                .on("show_contextmenu.jstree", function (objNode, x, y) {
                    //initialze properties when context menu is shown
                    lang.initializeProperties($('.jstree-contextmenu'));
                });

            $jsTree
                .on('move_node.jstree', function (e, dataEvent) {
                    moveNode(dataEvent);
                });

            $jsTree
                .on('ready.jstree', function (e, data) {
                    treeContext.selectNodesOnLoad(e, data);
                });

            $jsTree
                .on('load_node.jstree', function (e, data) {
                    treeContext.selectNodesOnLoad(e, data);
                });

            //4. init jstree draggable for lists
            $('td.treedrag.jstree-listdraggable').on('mousedown', this.listDnd);
        };


        /**
         * Select nodes after the tree has loaded
         *      if treeContext.initiallySelectedNodes contains id's, select all nodes with the given id's in the tree
         *      otherwise the last id in array treeContext.treeviewExpanders is automatically being selected
         *
         * @param e - event
         * @param data - data of the event
         */
        this.selectNodesOnLoad = function(e, data) {
            var treeInstance = data.instance;

            /*Select nodes after the tree has loaded
                if treeContext.initiallySelectedNodes contains id's, select all nodes with the given id's in the tree
                otherwise the last id in array treeContext.treeviewExpanders is automatically being selected
             */
            if(treeContext.initiallySelectedNodes instanceof Array && treeContext.initiallySelectedNodes.length > 0) {
                treeInstance.select_node(treeContext.initiallySelectedNodes);
            } else if(treeContext.treeviewExpanders instanceof Array && treeContext.treeviewExpanders.length > 0) {
                var strSelectId = treeContext.treeviewExpanders[treeContext.treeviewExpanders.length-1];
                treeInstance.select_node(strSelectId);
            }
        }
    };




    /**
     * Get the current tree instance
     *
     * @returns {*}
     */
    kajonatree.helper.getTreeInstance = function () {
        var treeId = $('.treeDiv').first()[0].id;
        return $.jstree.reference('#' + treeId);

    };

    /**
     *  Creates the contextmenu
     *
     * @param o - the node
     * @param cb - callback function
     */
    kajonatree.contextmenu.createDefaultContextMenu = function (o, cb) {

        if (o.data.hasOwnProperty("loadall")) return null;

        var objItems = {
            "expand_all": {
                "label": "<span data-lang-property=\"system:commons_tree_contextmenu_loadallsubnodes\"></span>",
                "action": kajonatree.contextmenu.openAllNodes,
                "icon": "fa fa-sitemap"
            }
        };

        return objItems;
    };


    /**
     *  Each time a node should be select, this method is being fired via the conditionalselect plugin.
     *  Handles conitional select events.
     *
     * @param objNode - the node to be selected
     * @param event - the event being fired
     *
     */
    kajonatree.conditionalselect.handleConditionalSelect = function (objNode, event) {

        //handle on click events
        if (event.type == "click") {

            if (objNode.hasOwnProperty("data") && objNode.data.hasOwnProperty("loadall")) {
                var parent = kajonatree.helper.getTreeInstance().get_parent(objNode);
                var parentObj = kajonatree.helper.getTreeInstance().get_node(parent);
                parentObj.data.loadall = true;
                $('#' + objNode.id).addClass('jstree-loading');

                var openNodes = kajonatree.getAllOpenNodes();
                kajonatree.helper.getTreeInstance().load_node(parentObj, function () {
                    kajonatree.helper.getTreeInstance().open_node(openNodes);
                });

                return true;
            }

            //if node contains a_attr with href -> relaod page
            if (objNode.a_attr) {
                if (objNode.a_attr.href) {
                   document.location.href = objNode.a_attr.href;//Document reload
                }
            }
        }

        return true;
    };

    /**
     * Function returns all opened nodes in the tree
     *
     * @returns {array}
     */
    kajonatree.getAllOpenNodes = function () {
        var openedNodes = [];
        $("li.jstree-open").each(function () {
            var $this = $(this);
            openedNodes.push($this.attr("id"));
        });

        return openedNodes;
    };

    /**
     * Function to open all nodes via the contextmenu
     *
     * @param data
     */
    kajonatree.contextmenu.openAllNodes = function (data) {
        var objTreeInstance = $.jstree.reference(data.reference),
            objNode = objTreeInstance.get_node(data.reference);

        /*Check if node was already loaded (also check if parent node was loaded)*/
        var arrNodesToCheck = objNode.parents;
        arrNodesToCheck.unshift(objNode.id);
        var bitAlreadyLoaded = false;

        for(var i = 0; i < arrNodesToCheck.length; i++) {
            var objCurrNode = objTreeInstance.get_node(arrNodesToCheck[i]);

            if(!objCurrNode.data) {
                objCurrNode.data = {};
            }

            if(objCurrNode.data.jstree_loadallchildnodes) {
                bitAlreadyLoaded = true;
                break;
            }
        }

        //only load if have not been loaded yet, else just open all nodes
        if(!bitAlreadyLoaded) {
            objNode.data.jstree_loadallchildnodes = true;
            objTreeInstance.load_node(objNode, function(node){
                objTreeInstance.open_all(node);
            });
        }
        else {
            //all child nodes are already loaded
            objTreeInstance.open_all(objNode);
        }
    };


    kajonatree.toggleInitial = function(strTreeId) {
        var treeStates = cacheManager.get("treestate");
        if(treeStates != null && treeStates != '') {
            treeStates = JSON.parse(treeStates);

            if(treeStates[strTreeId] == 'false') {
                kajonatree.toggleTreeView(strTreeId);
            }
        }
    };

    kajonatree.toggleTreeView = function(strTreeId) {
        var $treeviewPane = $(".treeViewColumn[data-kajona-treeid="+strTreeId+"]");
        var $contentPane = $(".treeViewContent[data-kajona-treeid="+strTreeId+"]");
        var treeStates = cacheManager.get("treestate");
        if(treeStates == null || treeStates == '') {
            treeStates = {};
        }
        else {
            treeStates = JSON.parse(treeStates);
        }
        if(!treeStates[strTreeId])
            treeStates[strTreeId] = 'true';

        if($treeviewPane.hasClass("col-md-4")) {
            $treeviewPane.addClass("hidden").removeClass("col-md-4");
            $contentPane.addClass("col-md-12").removeClass("col-md-8");
            treeStates[strTreeId] = 'false';

        } else {
            $treeviewPane.addClass("col-md-4").removeClass("hidden");
            $contentPane.addClass("col-md-8").removeClass("col-md-12");
            treeStates[strTreeId] = 'true';
        }

        cacheManager.set("treestate", JSON.stringify(treeStates));
        return false;
    };

    return kajonatree;

});
