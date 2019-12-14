use Vendimia\View;
/*
--------------------------------------------------------------------------------
Basic default view layout. Remember, there must be always at least one
$this->content()" method call with no arguments in the layout.

The files 'vendimia_default_header' and 'vendimia_default_footer' are located
in the 'base/views' directory on the Vendimia installation path.
--------------------------------------------------------------------------------
*/

// The 'vendimia_default_header' view file contains all the standard HTML
// headers, up to the <body> tag.
$this->insert('vendimia_default_header');

// The 'vendimia_message' view file contains the message <div> tag for the
// Vendimia\Message methods, like message() o warning().
$this->insert('vendimia_messages');

// This draws the actual view. If you create your own layout,
// make sure you put this method with no parameters.
$this->content();

// The 'vendimia_default_footer' contains a small footer, the </body> and
// </html> closing tags.
$this->insert('vendimia_default_footer');
