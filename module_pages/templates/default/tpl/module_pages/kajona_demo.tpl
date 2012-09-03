<?xml version="1.0"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link href="_webpath_/templates/default/css/kajona.css?_system_browser_cachebuster_" rel="stylesheet" type="text/css" />
    %%kajona_head%%
    <title>%%additionalTitle%%%%title%% | Kajona³</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="robots" content="index, follow" />
    <meta name="description" content="%%description%%" />
    <meta name="keywords" content="%%keywords%%" />
    <link rel="alternate" type="application/rss+xml" title="Kajona³ news" href="_webpath_/xml.php?module=news&amp;action=newsFeed&amp;feedTitle=kajona_news" />
    <link rel="shortcut icon" href="_webpath_/favicon.ico" type="image/x-icon" />
</head>
<body>

<div id="bodyContainer">

    <div id="logoHeaderHeader">
        <div id="headerLogo"></div>
        <div id="headerLogoR"></div>
        <div class="clearer"></div>
    </div>
    <div id="imageHeader"></div>

    <div id="mainContainer">
        <div id="portalNaviContainer">
            <div id="languageSwitchContainer">
                %%masterlanguageswitch_languageswitch%%
            </div>
            <div id="pNaviContainer">
                %%masterportalnavi_navigation%%
            </div>
            <div class="clearer"></div>
        </div>
        <div class="contentSpacer"></div>
        <div id="mainContentContainer">
            <div id="mainNaviContainer">

                %%mastermainnavi_navigation%%
            </div>
            <div id="contentContainer">
                <!-- Please note that the following list is only for demo-purposes.
                     When using the template for "real" installations, the list of
                     placeholders should be stripped down to a minimum. -->
                %%headline_row%%
                %%text_paragraph%%
                %%picture1_image%%
                %%news_news%%
                %%gb1_guestbook%%
                %%dl1_downloads%%
                %%bilder_gallery%%
                %%bilder2_galleryRandom%%
                %%formular_form|tellafriend%%
                %%results_search%%
                %%sitemap_navigation%%
                %%faqs_faqs%%
                %%comments_postacomment%%
                %%mixed_rssfeed|tagto|imagelightbox|portallogin|portalregistration|lastmodified|rendertext|tagcloud|downloadstoplist|textticker%%
                %%mixed2_portalupload|directorybrowser%%
                %%mixed3_flash|mediaplayer|tags|eventmanager%%
                %%list_userlist%%
                %%votings_votings%%

                <div align="right">
                <div id="fb-root"></div>
                <script>(function(d, s, id) {
                  var js, fjs = d.getElementsByTagName(s)[0];
                  if (d.getElementById(id)) {return;}
                  js = d.createElement(s); js.id = id;
                  js.src = "//connect.facebook.net/en_US/all.js#appId=141503865945925&xfbml=1";
                  fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));</script>

                <div class="fb-like" data-href="https://www.facebook.com/pages/Kajona%C2%B3/156841314360532" data-send="false" data-layout="button_count" data-width="60" data-show-faces="false"></div>
                </div>
            </div>
            <div class="clearer"></div>
        </div>
        <div class="contentSpacer"></div>
        <div id="footerContainer">%%copyright%%</div>
    </div>
</div>

</body>
</html>