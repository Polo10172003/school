<?php
 
function renderHeader($page_title = null) {
    if ($page_title) {
        $GLOBALS['page_title'] = $page_title;
    }
    include __DIR__ . '/header.php';
}

function renderFooter() {
    include __DIR__ . '/footer.php';
}

function renderPage($page_title = null, $content_callback = null) {
    renderHeader($page_title);
    
    if (is_callable($content_callback)) {
        echo $content_callback();
    }
    
    renderFooter();
}

function setActiveNav($active_page) {
    $GLOBALS['active_nav'] = $active_page;
}
?>
