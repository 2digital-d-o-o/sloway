<?php foreach ($sections as $name => $title): ?>
.<?=$name?> {
	border: 1px solid silver;
	background-repeat: no-repeat;
	position: relative;
	padding-top: 14px;
	margin: 10px 0;
}
.<?=$name?> > p {
	margin: 0;
}
.<?=$name?>:before {
	content: '<?=$title?>';
	position: absolute; top: 1px; left: 0; font-size: 10px; color: grey;
}
<?php endforeach ?>
