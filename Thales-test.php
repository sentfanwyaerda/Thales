<?php 
require_once(dirname(__FILE__).'/Thales.php');

/*************************
 * DEBUG
 *************************/

$Thales = new Thales();
$Thales->load_menu();

$Thales->add_item('/Mailbox/Trash', '{"url":"#","notice":{"class":"bg-red","text":"125","tag":"small"}}', TRUE);
$Thales->add_item('/Mailbox/Other/Send messages', array('url'=>'#'), TRUE);
$Thales->add_item('/Mailbox/Other/Incomming messages', '#', TRUE);

$menu = $Thales->generate_menu();
print $menu;
?>