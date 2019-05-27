<?php 
namespace welcome; use Vendimia;

$this->setLayout('vendimia_default');
$this['_vendimia_page_title'] = 'Welcome to your new Vendimia project!';
?>
<div class="container">
<h1>Welcome to your new Vendimia project!</h1>
<p>Now, you have to do a few things more before start:</p>

<ul>
<li>Adjust the <tt>config/settings.php</tt> file to your needs (specially in the <tt>databases</tt> section, if you'll use one).</li>

<li>Create new applications for your project. You can use the <tt>Vendimia</tt> script for this task:</li>

<pre>cd <?=Vendimia\PROJECT_PATH?>

vendimia new app my_app</pre>

<li>Edit the <tt>config/routes.php</tt> file for setting the default app for this project. It will be shown when accessing the root URL of this project. Add this line inside the array:

<pre>
Rule::default()->target('my_default_app'),
</pre>
</li>
<li>And that's it. Have fun coding!</li>

</ul>
</div>
