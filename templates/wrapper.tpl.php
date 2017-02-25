<?php
$perfData = array(
	'Image::genThumb',
	'Post::Fix'
);
$pdO = '';
$showPerfData = false;
foreach ($perfData as $what) {
	if (isset($GLOBALS['sw_elapsed'][$what])) {
		$pdO .= '<li>';
		$pdO .= sprintf('%s: %01.5f seconds', $what, $GLOBALS['sw_elapsed'][$what]);
		$pdO .= '</li>';
		$showPerfData = true;
	}
}
?><!DOCREF html>
<html>
	<head>
		<title>/vg/station Test - <?=$this->title?></title>
		<link rel="stylesheet" href="<?=WEB_ROOT ?>/style.php/style.scss" />
		<link rel="stylesheet" href="<?=WEB_ROOT ?>/jquery.tagit.css" />
		<link rel="stylesheet" href="<?=WEB_ROOT ?>/jquery-ui-1.10.0.custom.min.css" />
		<script src="<?=WEB_ROOT ?>/js/jquery-1.9.0.js" type="text/javascript"></script>
		<script src="<?=WEB_ROOT ?>/js/jquery-ui-1.10.0.custom.min.js" type="text/javascript"></script>
		<script src="<?=WEB_ROOT ?>/js/tag-it.min.js" type="text/javascript"></script>
		<?=$this->head ?>
	</head>
	<body>
		<section id="wrap">
			<section id="header">
				<h1>/fail/station</h1>
				<ul id="plinks">
				<?php foreach($this->links['/'] as $name=>$dat):?>
					<li id="link-<?=$name ?>">
						<a href="<?=$dat['url'] ?>"><img src="<?=$dat['image']?>" alt="<?=$dat['desc']?>" /></a>
					</li>
				<?php endforeach; ?>
				</ul>
				<?php if($this->session!=false):?>
				<div id="sessinfo">
					Welcome back, <?=$this->session->ckey?> (<?=$this->session->role?>)
				</div>
				<?php endif?>
			</section>
			<section id="content">
				<?=$this->body ?>
			</section>
		</section>
		<section id="footer">
			<ul>
				<li class="first">vgstation-web</li>
					<li><?=sprintf('Took %01.5f seconds to render this page.', stopwatch('start')) ?></li>
			</ul>
			<?php if($showPerfData):?>
			<ul>
				<li class="first" style="color:black">Performance Data:</li><?=$pdO ?>
			</ul>
			<?php endif; ?>
		</section>
	</body>
</html>
