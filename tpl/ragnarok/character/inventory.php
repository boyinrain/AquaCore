<?php
/**
 * @var $inventory_size int
 * @var $inventory      \Aqua\Ragnarok\Item[]
 * @var $paginator      \Aqua\UI\Pagination
 * @var $page           \Page\Main\Ragnarok\Server\Char
 */

$page->theme->footer->enqueueScript('cardbmp')
                    ->type('text/javascript')
                    ->append('
(function($) {
	$("[ac-ro-card]").tooltip({
		tooltipClass: "ac-card-bmp",
		position: {
			my: "center+5 bottom-7",
			at: "center-5 top"
		},
		hide: null,
		show: null,
		content: function() {
			return $("<span/>")
				.append($("<div/>").addClass("ac-tooltip-top"))
				.append($("<div/>").width(150).addClass("ac-tooltip-content").append($("<img/>").attr("src", $(this).attr("ac-ro-card"))))
				.append($("<div/>").addClass("ac-tooltip-bottom"));
		}
	});
})(jQuery);
');
$search_type = $page->request->uri->getString('t');
$base_url = $page->charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
?>
<table class="ac-table">
	<thead>
	<tr>
		<td colspan="7">
			<form method="GET">
				<?php echo ac_form_path()?>
				<div class="ac-storage-types" style="float: left; line-height: 30px">
					<input id="ac_storage-use" type="radio" name="t" value="use" <?php echo ($search_type == 'use' ? 'checked' : '')?>>
					<label for="ac_storage-use"><?php echo __('ragnarok-item-type', '2')?></label>
					<input id="ac_storage-misc" type="radio" name="t" value="misc" <?php echo ($search_type == 'misc' ? 'checked' : '')?>>
					<label for="ac_storage-misc"><?php echo __('ragnarok-item-type', '3')?></label>
					<input id="ac_storage-weapon" type="radio" name="t" value="weapon" <?php echo ($search_type == 'weapon' ? 'checked' : '')?>>
					<label for="ac_storage-weapon"><?php echo __('ragnarok-item-type', '4')?></label>
					<input id="ac_storage-armor" type="radio" name="t" value="armor" <?php echo ($search_type == 'armor' ? 'checked' : '')?>>
					<label for="ac_storage-armor"><?php echo __('ragnarok-item-type', '5')?></label>
					<input id="ac_storage-egg" type="radio" name="t" value="egg" <?php echo ($search_type == 'egg' ? 'checked' : '')?>>
					<label for="ac_storage-egg"><?php echo __('ragnarok-item-type', '7')?></label>
					<input id="ac_storage-card" type="radio" name="t" value="card" <?php echo ($search_type == 'card' ? 'checked' : '')?>>
					<label for="ac_storage-card"><?php echo __('ragnarok-item-type', '6')?></label>
					<input id="ac_storage-ammo" type="radio" name="t" value="ammo" <?php echo ($search_type == 'ammo' ? 'checked' : '')?>>
					<label for="ac_storage-ammo"><?php echo __('ragnarok-item-type', '10')?></label>
					<input id="ac_storage-all" type="radio" name="t" value="" <?php echo (empty($search_type) ? 'checked' : '')?>>
					<label for="ac_storage-all"><?php echo __('application', 'all')?></label>
				</div>
				<div style="float:right">
					<input type="text" name="s" value="<?php echo htmlspecialchars($page->request->uri->getString('s'))?>">
					<input type="submit" value="<?php echo __('application', 'search')?>">
				</div>
			</form>
		</td>
	</tr>
	<tr class="alt">
		<td></td>
		<td><?php echo __('ragnarok', 'name')?></td>
		<td><?php echo __('ragnarok', 'amount')?></td>
		<td colspan="4"><?php echo __('ragnarok', 'cards')?></td>
	</tr>
	</thead>
	<tbody>
<?php if(empty($inventory)) : ?>
		<tr>
			<td colspan="7" style="text-align: center; font-style: italic;"><?php echo __('application', 'no-search-results')?></td>
		</tr>
<?php else : foreach($inventory as $item) : ?>
		<tr>
<?php if($item->identified) : ?>
			<td class="ac-item-icon"><img src="<?php echo ac_item_icon($item->itemId)?>"></td>
			<td class="ac-item-name"><a href="<?php echo $base_url . $item->itemId?>"><?php echo $item->name(false)?></a></td>
			<td class="ac-item-amount"><?php echo number_format($item->amount)?></td>
			<?php
			for($i = 0; $i < 4; ++$i) {
				$item->card($i, $card_id, $enchanted);
				if($enchanted) { ?>
					<a href="<?php echo $base_url . $card_id ?>"><img src="<?php echo ac_item_icon($card_id)?>"></a>
				<?php } else if($item->slots < ($i + 1)) { ?>
					<td class="ac-card-slot ac-slot-disabled"></td>
				<?php } else if($card_id) { ?>
					<td class="ac-card-slot" ac-ro-card="<?php echo ac_item_cardbmp($card_id)?>">
						<a href="<?php echo $base_url . $card_id?>"></a>
					</td>
				<?php } else { ?>
					<td class="ac-card-slot ac-slot-empty"></td>
				<?php }} ?>
<?php else : ?>
	<td class="ac-item-icon ac-item-unidentified">
		<img src="<?php echo \Aqua\URL?>/assets/images/icons/unidentified.png">
	</td>
	<td class="ac-item-name"><i><?php echo __('ragnarok', 'unidentified')?></i></td>
	<td class="ac-item-amount"><?php echo number_format($item->amount)?></td>
	<td class="ac-card-slot ac-slot-disabled"></td>
	<td class="ac-card-slot ac-slot-disabled"></td>
	<td class="ac-card-slot ac-slot-disabled"></td>
	<td class="ac-card-slot ac-slot-disabled"></td>
<?php endif; ?>
		</tr>
<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="7" style="text-align: center">
			<?php echo $paginator->render()?>
		</td>
	</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($inventory_size === 1 ? 's' : 'p'), number_format($inventory_size))?></span>
