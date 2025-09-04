<?php
/**
 * Template Helper Functions
 * This file contains functions to help with template rendering
 */

/**
 * Render the page header including HTML head, CSS, and navbar
 * @param string $page_title Optional custom page title
 */
function renderHeader($page_title = null) {
    if ($page_title) {
        $GLOBALS['page_title'] = $page_title;
    }
    include __DIR__ . '/header.php';
}

/**
 * Render the page footer including footer HTML and closing tags
 */
function renderFooter() {
    include __DIR__ . '/footer.php';
}

/**
 * Render a complete page with header and footer
 * @param string $page_title Optional custom page title
 * @param callable $content_callback Function that returns the page content
 */
function renderPage($page_title = null, $content_callback = null) {
    renderHeader($page_title);
    
    if (is_callable($content_callback)) {
        echo $content_callback();
    }
    
    renderFooter();
}

/**
 * Set active navigation item
 * @param string $active_page The page that should be marked as active
 */
function setActiveNav($active_page) {
    $GLOBALS['active_nav'] = $active_page;
}
?>
