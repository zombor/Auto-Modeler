<?php if ( ! empty($errors)):?>
	<ul class="errors">
	<?php foreach ($errors as $field => $error):?>
		<li rel="<?php echo $field ?>"><?php echo $error ?></li>
	<?php endforeach;?>
	</ul>
<?php endif;?>