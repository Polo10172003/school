<?php
// Include template helper
require_once 'templates/template_helper.php';

// Set page title
$page_title = 'Example Page - Escuela de Sto. Rosario';

// Render the page using the template system
renderPage($page_title, function() {
    ob_start();
    ?>
    
    <div class="container mt-5">
        <h1 class="text-center mb-4">Example Page</h1>
        <p class="lead">This is an example of how to use the template system.</p>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Benefits of Template System</h3>
                <ul>
                    <li>Reusable navbar and footer</li>
                    <li>Easy to maintain</li>
                    <li>Consistent styling across pages</li>
                    <li>Clean and organized code</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h3>How to Use</h3>
                <ol>
                    <li>Include template_helper.php</li>
                    <li>Set your page title</li>
                    <li>Use renderPage() function</li>
                    <li>Write your content in the callback</li>
                </ol>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
});
?>
