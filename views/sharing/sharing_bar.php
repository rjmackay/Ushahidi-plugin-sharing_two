<div class="cat-filters clearingfix" style="margin-top:20px;">
	<strong><?php echo Kohana::lang('sharing_two.site_filter');?>
		<span>[<a href="javascript:toggleLayer('sharing_switch_link','sharing_switch')" id="sharing_switch_link">
			<?php echo Kohana::lang('ui_main.hide'); ?></a>]
		</span>
	</strong>
</div>
		<ul id="sharing_switch" class="category-filters">
			<li><a href="#" id="share_all" class="active">
				<div class="swatch" style="background-color:#<?php echo Kohana::config('settings.default_map_all'); ?>"></div>
				<div><?php echo Kohana::lang('sharing_two.all_sites') ?></div>
			</a></li>
			<li><a href="#" id="share_main">
				<div class="swatch" style="background-color:#<?php echo Kohana::config('settings.default_map_all'); ?>"></div>
				<div><?php echo Kohana::config('settings.site_name') ?></div>
			</a></li>
			<?php
			
			foreach ($shares as $share => $share_info)
			{
				$sharing_name = $share_info[0];
				$sharing_color = $share_info[1];
				echo '<li><a href="#" id="share_'. $share .'"><div class="swatch" style="background-color:#'.$sharing_color.'"></div>
				<div>'.$sharing_name.'</div></a></li>';
			}
			?>
		</ul>
