<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<div class="Info">
	<?php echo T(sprintf('This is the secret key for online cron services, url to call: %1$s',
		Url('/plugin/timertick/?TimerTickToken=' . $this->Form->GetValue('Plugins.UsefulFunctions.TimerTick.SecretKey'), True)
	)); ?>
</div>
<ul>
	<li>
		<?php
			echo $this->Form->Label('Tick Secret Key', 'Plugins.UsefulFunctions.TimerTick.SecretKey');
			echo $this->Form->TextBox('Plugins.UsefulFunctions.TimerTick.SecretKey');
		?>
	</li>
</ul>
<?php 
echo $this->Form->Button('Save', array('class' => 'Button SliceSubmit')); 
echo $this->Form->Close();
?>