<?php
$ADMIN_FLAGS= $this->ADMIN_FLAGS;

?>
<h1>Administrators</h1>
<?php if($this->isAdmin):?>
<form action="<?=fmtURL('admins')?>" method="post">
<?php endif;?>
  <div class='table'>
  	<div>
  		<span class='header'>
  			Name
  		</span>
  		<span class='header'>
  		    Rank
  		</span>
  		<?php foreach($ADMIN_FLAGS as $flag=>$fname):?>
  		<span class='header'>
  			<?=$fname?>
  		</span>
  		<?php endforeach;?>
  	</div>
<?php foreach($this->admins as $row):
    $admin=Admin::FromRow($row);
    $showControls = $this->isAdmin && $this->user->ID!=$admin->ID && $this->user->canEdit($admin);
    //var_dump($admin);
    ?>
    <div>
		<span class="clmName">
      <?=(!$showControls) ? '' : new Input('checkbox',"ckeys[]",$admin->CKey);?>
			<?=$admin->CKey?>
		</span>
		<span class="clmRank">
			<?=$admin->Rank?>
		</span>
		<?php
		foreach($ADMIN_FLAGS as $flag=>$name) {
			$hasFlag=($admin->Flags & $flag) == $flag;
	    $span = new Element('span',array('class'=>'clm'.$name));
      $span->addClass('flags');
      $span->addClass($hasFlag?'flagset':'flagunset');
      if($showControls)
      {
          $child = new Input('checkbox',"flags[{$admin->CKey}][]",$flag,array('title'=>$name));
          if($hasFlag)
              $child->setAttribute('checked','checked');
          $span->addChild($child);
      } else {
          $span->addChild($hasFlag?'&#x2713;':'&#x2717;');
      }
      echo $span;
		}
		?>
	</div>
<?php endforeach;?>
</div>
  <?php if($this->isAdmin):?>
  <div class="controls">
    <button type="submit" name="act" value="update">Update</button>
    <button type="submit" name="act" value="delete">Delete</button>
    <select name="rank">
    <?php
    echo new Element('option', ['value'=>'','selected'=>'selected', 'disabled'=>'disabled', 'hidden'=>'hidden'], 'Rank');
    foreach($this->ADMIN_RANKS as $name=>$flags) {
      $opts = ['value'=>$name];
      echo new Element('option', $opts, $name);
    }
    ?>
    </select>
    <button type="submit" name="act" value="setrank">Set Rank</button>
  </div>
  </form>
  <?php endif;?>
</div>
