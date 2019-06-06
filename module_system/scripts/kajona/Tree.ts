import $ from 'jquery'
import 'jstree'
import 'jstree/dist/themes/default/style.min.css'
import Ajax from './Ajax'
import Lang from './Lang'
import CacheManager from './CacheManager'

class Helper {
    /**
     * Get the current tree instance
     *
     * @returns {*}
     */
    public static getTreeInstance () {
        var treeId = $('.treeDiv').first()[0].id
        return $.jstree.reference('#' + treeId)
    }

    /**
     * Check if the node is a "load all" node
     * @param objNode
     *
     * @returns {*}
     */
    public static isLoadAllNode (objNode: any) {
        return (
            objNode.hasOwnProperty('data') &&
            objNode.data.hasOwnProperty('loadall')
        )
    }
}

class ContextMenu {
    /**
     *  Creates the contextmenu
     *
     * @param o - the node
     * @param cb - callback function
     */
    public static createDefaultContextMenu (o: any, cb: Function) {
        if (Helper.isLoadAllNode(o)) return null

        var objItems = {
            expand_all: {
                label:
                    '<span data-lang-property="system:commons_tree_contextmenu_loadallsubnodes"></span>',
                action: ContextMenu.openAllNodes,
                icon: 'fa fa-sitemap'
            }
        }

        return objItems
    }

    /**
     * Function to open all nodes via the contextmenu
     *
     * @param data
     */
    public static openAllNodes (data: any) {
        var objTreeInstance = $.jstree.reference(data.reference)
        var objNode = objTreeInstance.get_node(data.reference)

        /* Check if node was already loaded (also check if parent node was loaded) */
        var arrNodesToCheck = objNode.parents
        arrNodesToCheck.unshift(objNode.id)
        var bitAlreadyLoaded = false

        for (var i = 0; i < arrNodesToCheck.length; i++) {
            var objCurrNode = objTreeInstance.get_node(arrNodesToCheck[i])

            if (!objCurrNode.data) {
                objCurrNode.data = {}
            }

            if (objCurrNode.data.jstree_loadallchildnodes) {
                bitAlreadyLoaded = true
                break
            }
        }

        // only load if have not been loaded yet, else just open all nodes
        if (!bitAlreadyLoaded) {
            objNode.data.jstree_loadallchildnodes = true
            objTreeInstance.load_node(objNode, function (node) {
                objTreeInstance.open_all(node)
            })
        } else {
            // all child nodes are already loaded
            objTreeInstance.open_all(objNode)
        }
    }
}

class ConditionalSelect {
    /**
     *  Each time a node should be select, this method is being fired via the conditionalselect plugin.
     *  Handles conitional select events.
     *
     * @param objNode - the node to be selected
     * @param event - the event being fired
     *
     */
    public static handleConditionalSelect (objNode: any, event: any) {
        // handle on click events
        if (event.type === 'click') {
            // if "load all" node was clicked
            if (Helper.isLoadAllNode(objNode)) {
                var parent = Helper.getTreeInstance().get_parent(objNode)
                var parentObj = Helper.getTreeInstance().get_node(parent)
                parentObj.data.loadall = true
                $('#' + objNode.id).addClass('jstree-loading')

                var openNodes = Tree.getAllOpenNodes()
                Helper.getTreeInstance().load_node(parentObj, function () {
                    Helper.getTreeInstance().open_node(openNodes)
                    parentObj.data.loadall = false
                })

                return true
            }

            // if node contains a_attr with href -> relaod page
            if (objNode.a_attr) {
                if (objNode.a_attr.href) {
                    document.location.href = objNode.a_attr.href // Document reload
                }
            }
        }

        return true
    }
}

class JSTree {
    private loadNodeDataUrl: string = null
    private rootNodeSystemid: string = null
    private treeConfig: any = null // @see class \Kajona\System\System\SystemJSTreeConfig for structure
    private treeId: string = null
    private treeviewExpanders: Array<string> = null // array of ids
    private initiallySelectedNodes: Array<string> = null // array of ids

    /**
     * Moves nodes below another node.
     * Triggers a reload of the page after node was moved
     *
     * @param data
     * @returns {boolean}
     */
    public moveNode (data: any) {
        // node data
        var strNodeId = data.node.id
        var strNewParentId = data.parent
        var strOldParentId = data.old_parent
        var intNewPostiton = data.position
        var intOldPostiton = data.old_position

        /* Get table row which should be moved */
        var $objTableRowMoved = $(
            'tr[data-systemid=' + strNodeId + ']'
        ).closest('tbody')

        // same parent
        if (strNewParentId === strOldParentId) {
            /* Move table row to according position */
            if ($objTableRowMoved.length > 0) {
                var arrElementsInTable = $objTableRowMoved
                    .closest('table')
                    .find('tbody')

                if (intOldPostiton > intNewPostiton) {
                    $(arrElementsInTable[intOldPostiton]).insertBefore(
                        $(arrElementsInTable[intNewPostiton])
                    )
                }
                if (intOldPostiton < intNewPostiton) {
                    $(arrElementsInTable[intOldPostiton]).insertAfter(
                        $(arrElementsInTable[intNewPostiton])
                    )
                }
            }

            /* Call server */
            Ajax.genericAjaxCall(
                'system',
                'setAbsolutePosition',
                strNodeId + '&listPos=' + (intNewPostiton + 1),
                function (data: any, status: string, jqXHR: XMLHttpRequest) {
                    Ajax.regularCallback(data, status, jqXHR)
                }
            )
        } else if (strNewParentId !== strOldParentId) {
            // different parent
            /* hide table row */
            if ($objTableRowMoved.length > 0) {
                $objTableRowMoved.hide()
            }

            /* Call server */
            Ajax.genericAjaxCall(
                'system',
                'setPrevid',
                strNodeId + '&prevId=' + strNewParentId,
                function (data: any, status: string, jqXHR: XMLHttpRequest) {
                    if (status === 'success') {
                        Ajax.genericAjaxCall(
                            'system',
                            'setAbsolutePosition',
                            strNodeId + '&listPos=' + (intNewPostiton + 1),
                            function (
                                data: any,
                                status: string,
                                jqXHR: XMLHttpRequest
                            ) {
                                Ajax.regularCallback(data, status, jqXHR)
                            }
                        )
                    } else {
                        Ajax.regularCallback(data, status, jqXHR)
                    }
                }
            )
        }

        return true
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
    public checkMoveNode (
        node: any,
        // eslint-disable-next-line camelcase
        node_parent: any,
        // eslint-disable-next-line camelcase
        node_position: number,
        more: any
    ) {
        var targetNode = more.ref
        var strDragId = node.id
        var strTargetId = targetNode.id
        var strInsertPosition = more.pos // "b"=>before, "a"=>after, "i"=inside

        // 1. user can only move node if he has right on the dragged node and the parent node
        if (!node.data.rightedit && !node_parent.data.rightedit) {
            return false
        }

        // 2. dragged node already direct childnode of target?
        var arrTargetChildren = targetNode.children
        if ($.inArray(strDragId, arrTargetChildren) > -1) {
            return false
        }

        // 3. dragged node is parent of target?
        var arrTargetParents = targetNode.parents
        if ($.inArray(strDragId, arrTargetParents) > -1) {
            return false // TODO maybe not needed, already check by jstree it self
        }

        // 4. dragged node same as target node?
        if (strDragId === strTargetId) {
            return false // TODO maybe not needed, already check by jstree it self
        }

        // 5. Check if node is valid child of node_parent
        if (!this.isValidChildNodeForParent(node, node_parent)) {
            return false
        }

        // 6. Check node_parent is valid parent for node
        if (!this.isValidParentNodeForChild(node, node_parent)) {
            return false
        }

        return true
    }

    /**
     * Checks if given node is a valid child node for the given parent
     *
     * @param node
     * @param node_parent
     * @returns {boolean}
     */
    // eslint-disable-next-line camelcase
    public isValidChildNodeForParent (node: any, node_parent: any) {
        if (node.data.customtypes) {
            var curType = node.data.customtypes.type
            var arrValidChildrenTargetParent =
                node_parent.data.customtypes.valid_children

            if (arrValidChildrenTargetParent === null) {
                return true
            }

            // now check if the current type can be placed to the target node by checking the valid children
            if ($.inArray(curType, arrValidChildrenTargetParent) === -1) {
                // -1 == curType not in array
                return false
            }
        }

        return true
    }

    /*
     * Check node_parent is valid parent for node
     *
     * Determines if one of the parent nodes of the given node 'node' has check_parent_id_active set to true.
     *  If this is not the case, everything is ok -> return true
     *  If this is case it will checked, if the the new parent node 'node_parent' is somewhere within the path of the found node
     */
    // eslint-disable-next-line camelcase
    public isValidParentNodeForChild (node: any, node_parent: any) {
        var nodeWithDataAttribute = this.getNodeWithDataAttribute(
            node,
            'check_parent_id_active',
            true
        )
        if (nodeWithDataAttribute !== null) {
            var idToCheck = nodeWithDataAttribute.id
            var arrParents = node_parent.parents
            arrParents.unshift(node_parent.id)

            if ($.inArray(idToCheck, arrParents) === -1) {
                return false
            }
        }
        return true
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
    public getNodeWithDataAttribute (
        node: any,
        strAttribute: string,
        bitCheckParentNodesOnly?: boolean
    ) {
        // Check parent nodes
        if (bitCheckParentNodesOnly === true) {
            var tree = Helper.getTreeInstance()
            var arrParents = node.parents

            for (var i = 0, len = arrParents.length; i < len; i++) {
                var parentNode = tree.get_node(arrParents[i])
                if (parentNode.id === '#') {
                    // skip internal root node
                    return null
                }
                if (parentNode.data.hasOwnProperty(strAttribute)) {
                    return parentNode
                }
            }
        } else {
            // Check node directly
            if (node.data.hasOwnProperty(strAttribute)) {
                return node
            }
        }

        return null
    }

    /**
     * Callback used for dragging elements from the list to the tree
     *
     * @param e
     * @returns {*}
     */
    public listDnd (e: any) {
        var strSystemId = $(this)
            .closest('tr')
            .data('systemid')
        var strTitle = $(this)
            .closest('tr')
            .find('.title')
            .text()

        // Check if there a jstree instance (there should only one)
        var jsTree = $.jstree.reference('#' + this.treeId)

        // create basic node
        var objNode = {
            id: strSystemId,
            text: strTitle
        }

        // if a jstree instanse exists try to find a node for it
        if (jsTree != null) {
            var treeNode = jsTree.get_node(strSystemId)
            if (treeNode !== false) {
                objNode = treeNode
            }
        }

        var objData = {
            jstree: true,
            obj: $(this),
            nodes: [objNode]
        }
        var event = e
        var strHtml =
            '<div id="jstree-dnd" class="jstree-default"><i class="jstree-icon jstree-er"></i>' +
            strTitle +
            '</div>' // drag container
        return $.vakata.dnd.start(event, objData, strHtml)
    }

    /**
     * Initializes the jstree
     */
    public initTree () {
        var treeContext = this

        /* Create config object */
        var jsTreeObj: JSTreeStaticDefaults = {
            core: {
                /**
                 *
                 * @param operation operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
                 * @param node the selected node
                 * @param node_parent
                 * @param node_position
                 * @param more on dnd => more is the hovered node
                 * @returns {boolean}
                 */
                check_callback: function (
                    operation: string,
                    node: any,
                    // eslint-disable-next-line camelcase
                    node_parent: any,
                    // eslint-disable-next-line camelcase
                    node_position: number,
                    more: any
                ) {
                    // operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
                    // in case of 'rename_node' node_position is filled with the new node name

                    var bitReturn = false

                    if (operation === 'move_node') {
                        // check when dragging
                        bitReturn = true
                        if (more.dnd) {
                            bitReturn = treeContext.checkMoveNode(
                                node,
                                node_parent,
                                node_position,
                                more
                            )
                        }
                    }

                    if (operation === 'create_node') {
                        bitReturn = true // Check for assignment tree
                    }

                    return bitReturn
                },
                expand_selected_onload: true, // if left as true all parents of all selected nodes will be opened once the tree loads (so that all selected nodes are visible to the user)
                data: {
                    url: function (node: any) {
                        return treeContext.loadNodeDataUrl
                    },
                    data: function (node: any, cb: any) {
                        // params to be added to the given ulr on ajax call
                        var data: any = {}
                        if (node.id === '#') {
                            data.systemid = treeContext.rootNodeSystemid
                            data.jstree_initialtoggling =
                                treeContext.treeviewExpanders
                        } else {
                            data.systemid = node.id

                            if (node.data) {
                                data.jstree_loadallchildnodes =
                                    node.data.jstree_loadallchildnodes
                            }
                        }

                        if (Helper.isLoadAllNode(node)) {
                            data.loadall = true
                        }

                        return data
                    }
                },
                themes: {
                    url: null,
                    icons: false
                },
                animation: false
            },
            dnd: {
                check_while_dragging: true,
                is_draggable: function (arrArguments: any, event: any) {
                    var node = arrArguments[0]
                    var nodeDataAttribute = treeContext.getNodeWithDataAttribute(
                        node,
                        'is_not_draggable'
                    )
                    if (nodeDataAttribute !== null) {
                        return false
                    }

                    return true
                }
            },
            conditionalselect: ConditionalSelect.handleConditionalSelect,

            plugins: ['conditionalselect']
        }

        /* Extend Js Tree Object due to jsTreeConfig */
        if (this.treeConfig.checkbox) {
            let defaultsCheckbox: JSTreeStaticDefaultsCheckbox = {
                three_state: false // disable three state checkboxes by default
            }

            jsTreeObj.plugins.push('checkbox')
            jsTreeObj.checkbox = defaultsCheckbox
        }
        if (this.treeConfig.dnd) {
            jsTreeObj.plugins.push('dnd')
        }
        if (this.treeConfig.types) {
            jsTreeObj.plugins.push('types')
            jsTreeObj.types = this.treeConfig.types
        }
        if (this.treeConfig.contextmenu) {
            let defaultsContextMenu: JSTreeStaticDefaultsContextMenu = {
                items: this.treeConfig.contextmenu.items,
                show_at_node: false
            }

            jsTreeObj.plugins.push('contextmenu')
            jsTreeObj.contextmenu = defaultsContextMenu
        }

        /* Create the tree */
        var $jsTree = $('#' + this.treeId).jstree(jsTreeObj)

        /* Register events */
        $jsTree.on('show_contextmenu.jstree', function (objNode, x, y) {
            // initialze properties when context menu is shown
            Lang.initializeProperties($('.jstree-contextmenu'))
        })

        $jsTree.on('move_node.jstree', function (e, dataEvent) {
            treeContext.moveNode(dataEvent)
        })

        $jsTree.on('ready.jstree', function (e, data) {
            treeContext.selectNodesOnLoad(e, data, treeContext)
        })

        $jsTree.on('load_node.jstree', function (e, data) {
            treeContext.selectNodesOnLoad(e, data, treeContext)
        })

        // 4. init jstree draggable for lists
        $('td.treedrag.jstree-listdraggable').on('mousedown', this.listDnd)
    }

    /**
     * Select nodes after the tree has loaded
     *      if treeContext.initiallySelectedNodes contains id's, select all nodes with the given id's in the tree
     *      otherwise the last id in array treeContext.treeviewExpanders is automatically being selected
     *
     * @param e - event
     * @param data - data of the event
     * @param treeContext
     */
    public selectNodesOnLoad (e: any, data: any, treeContext: JSTree) {
        var treeInstance = data.instance

        /* Select nodes after the tree has loaded
            if treeContext.initiallySelectedNodes contains id's, select all nodes with the given id's in the tree
            otherwise the last id in array treeContext.treeviewExpanders is automatically being selected
         */
        if (
            treeContext.initiallySelectedNodes instanceof Array &&
            treeContext.initiallySelectedNodes.length > 0
        ) {
            treeInstance.select_node(treeContext.initiallySelectedNodes)
        } else if (
            treeContext.treeviewExpanders instanceof Array &&
            treeContext.treeviewExpanders.length > 0
        ) {
            var strSelectId =
                treeContext.treeviewExpanders[treeContext.treeviewExpanders.length - 1]
            treeInstance.select_node(strSelectId)
        }
    }
}

class Tree {
    private static helper: Helper = Helper
    private static contextmenu: ContextMenu = ContextMenu
    private static conditionalselect: ConditionalSelect = ConditionalSelect

    public static jstree () {
        return new JSTree()
    }

    /**
     * Function returns all opened nodes in the tree
     *
     * @returns {array}
     */
    public static getAllOpenNodes () {
        var openedNodes: Array<string> = []
        $('li.jstree-open').each(function () {
            var $this = $(this)
            openedNodes.push($this.attr('id'))
        })

        return openedNodes
    }

    public static toggleInitial (strTreeId: string) {
        var treeStates = CacheManager.get('treestate')
        if (treeStates != null && treeStates !== '') {
            treeStates = JSON.parse(treeStates)

            if (treeStates[strTreeId] === 'false') {
                Tree.toggleTreeView(strTreeId)
            }
        }
    }

    public static toggleTreeView (strTreeId: string) {
        var $treeviewPane = $(
            '.treeViewColumn[data-kajona-treeid=' + strTreeId + ']'
        )
        var $contentPane = $(
            '.treeViewContent[data-kajona-treeid=' + strTreeId + ']'
        )
        var treeStates = CacheManager.get('treestate')
        if (treeStates == null || treeStates === '') {
            treeStates = {}
        } else {
            treeStates = JSON.parse(treeStates)
        }
        if (!treeStates[strTreeId]) treeStates[strTreeId] = 'true'

        if ($treeviewPane.hasClass('col-md-4')) {
            $treeviewPane.addClass('hidden').removeClass('col-md-4')
            $contentPane.addClass('col-md-12').removeClass('col-md-8')
            treeStates[strTreeId] = 'false'
        } else {
            $treeviewPane.addClass('col-md-4').removeClass('hidden')
            $contentPane.addClass('col-md-8').removeClass('col-md-12')
            treeStates[strTreeId] = 'true'
        }

        CacheManager.set('treestate', JSON.stringify(treeStates))
        return false
    }
}
;(<any>window).Tree = Tree
export default Tree
