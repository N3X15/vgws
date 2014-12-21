<?php
$ADMIN_FLAGS= $this->ADMIN_FLAGS;

?>
<h1>Administrators</h1>
<div class='table'>
	<div>
		<span class='header'>
			Name
		</span>
		<span class='header'>
		    Rank
		</span>
		<?foreach($ADMIN_FLAGS as $flag=>$fname):?>
		<span class='header'>
			<?=$fname?>
		</span>
		<?endforeach;?>
        <?if($this->isAdmin):?>
        <span class='header'>
            Controls
        </span>
        <?endif;?>
	</div>
<?foreach($this->admins as $row):
    $admin=Admin::FromRow($row);
    $showControls = $this->isAdmin && $this->user->ID!=$admin->ID && $this->user->canEdit($admin);
    //var_dump($admin);
    ?>
    <?if($showControls):?>
    <form action="<?=fmtURL('admins')?>" method="post">
    <?else:?>
    <div>
    <?endif;?>
		<span class="clmName">
			<?=$admin->CKey?>
		</span>
		<span class="clmRank">
			<?=$admin->Rank?>
		</span>
		<?
		foreach($ADMIN_FLAGS as $flag=>$name) {
			$hasFlag=($admin->Flags & $flag) == $flag;
		    $span = new Element('span',array('class'=>'clm'.$name));
            $span->addClass('flags');
            $span->addClass($hasFlag?'flagset':'flagunset');
            if($showControls)
            {
                $child = new Input('checkbox','flag[]',$flag,array('title'=>$name));
                if($hasFlag)
                    $child->setAttribute('checked','checked');
                $span->addChild($child);
            } else {
                $span->addChild($hasFlag?'&#x2713;':'&#x2717;');
            }
            echo $span;
		}
		?>
    <?if($showControls):?>
		<span class="controls">
		    <input type="hidden" name="ckey" value="<?=$admin->CKey?>" />
            <button type="submit" name="act" value="update">Update</button>
            <button type="submit" name="act" value="delete">Delete</button>
		</span>
	</form>
	<?else:?>
	</div>
	<?endif;?>
<?endforeach;?>
</div>