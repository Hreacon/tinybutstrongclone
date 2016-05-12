# TinyButStrong Clone

A clone of the PHP framework TinyButStrong I wrote to test my PHP meddle. Despite being entirely inspired by TBS their documentation does not work with mine.

### Usage Example:
#### PHP:
$html = 'html/'; // folder where html is stored
$header='pageheader.html'; // header file
$footer='pagefooter.html'; // footer file
$template='about.html'; // template of html to show

include_once($class.'site_class.php');

// css files to include
$css[]='main';
$css[]='nav';
$variableName = "Some Text"
$site = new site();
$site->loadHeader($html.$header);
$site->loadTemplate($html . $template);
$site->loadFooter($html . $footer);
$site->mergeArray('blkcss', $css);
$site->show();

#### HTML
##### This:
<link rel="stylesheet" type="text/css" href="css/[blkcss.val;block=link].css" />
##### Becomes This:
<link rel="stylesheet" type="text/css" href="css/main.css" />
<link rel="stylesheet" type="text/css" href="css/nav.css" />

##### This:
[var.variableName]
##### Becomes This:
Some Text
