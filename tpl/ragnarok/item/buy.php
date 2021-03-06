<?php
use Aqua\Core\App;
/**
 * @var $accounts \Aqua\Ragnarok\Account[]
 * @var $cart     \Aqua\Ragnarok\Cart
 * @var $page     \Page\Main\Ragnarok\Server\Item
 */
$accounts_avail = count($accounts);
$return = base64_encode(App::request()->uri->url());
$max = App::settings()->get('ragnarok')->get('cash_shop_max_amount', 99);
$form_path = ac_form_path(array_merge($page->charmap->uri->path, array( 'item' )), 'cart', array(), true);
$base_url = $page->charmap->url(array(
	'path' => array( 'item' ),
	'action' => 'view',
	'arguments' => array( '' )
));
?>
<div class="ac-user-credits"><?php echo __('donation', 'credit-points', number_format(App::user()->account->credits))?></div>
<div style="display: table-cell; vertical-align: top; width: 100%; padding-right: 15px;">
	<table class="ac-table">
		<thead>
			<tr>
				<td colspan="4"><?php echo __('ragnarok', 'cart')?></td>
			</tr>
			<tr class="alt">
				<td></td>
				<td><?php echo __('ragnarok', 'name') ?></td>
				<td><?php echo __('ragnarok', 'price') ?></td>
				<td></td>
			</tr>
		</thead>
		<tbody>
<?php if(empty($cart->items)) : ?>
			<tr>
				<td colspan="4" class="ac-table-no-result"><?php echo __('ragnarok', 'empty-cart')?></td>
			</tr>
<?php else : foreach($cart->items as $id => $item) : ?>
			<tr>
				<td style="text-align: center"><img src="<?php echo ac_item_icon($id)?>"></td>
				<td style="vertical-align: middle"><a href="<?php echo $base_url . $id?>"><?php echo htmlspecialchars($item['name'])?></a></td>
				<td style="vertical-align: middle"><?php echo __('donation', 'credit-points', number_format($item['price']))?></td>
				<td style="width: 170px;">
					<form method="GET" action="<?php echo \Aqua\URL?>">
						<?php echo $form_path?>
						<input type="hidden" name="id" value="<?php echo $id?>">
						<input type="hidden" name="x" value="set">
						<input type="hidden" name="r" value="<?php echo $return ?>">
						<input style="width: 40px" type="number" name="a" value="<?php echo $item['amount']?>" min="0" max="<?php echo $max ?>" class="ac-update-cart-num">
						<input type="submit" value="<?php echo __('ragnarok', 'update-cart')?>" class="ac-update-cart-submit">
					</form>
				</td>
			</tr>
<?php endforeach; ?>
			<tr>
				<td colspan="2" style="text-align: center"></td>
				<td><?php echo __('donation', 'credit-points', number_format($cart->total))?></small></td>
				<td></td>
			</tr>
<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4">
					<?php if(!$cart->itemCount) : ?>
						<button class="ac-button" disabled><?php echo __('ragnarok', 'clear-cart')?></button>
					<?php else : ?>
						<a href="<?php echo $page->charmap->url(array(
							'path'   => array( 'item' ),
							'action' => 'cart',
							'query'  => array(
								'x' => 'clear',
							    'r' => $return
							)
						))?>">
							<button class="ac-button"><?php echo __('ragnarok', 'clear-cart')?></button>
						</a>
					<?php endif; ?>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
<div style="display: table-cell; vertical-align: top;">
<form method="POST">
	<table class="ac-table" style="width: 250px;">
		<thead>
			<tr>
				<td colspan="3"><?php echo __('ragnarok', 'account')?></td>
			</tr>
			<tr class="alt">
				<td></td>
				<td><?php echo __('ragnarok', 'username')?></td>
				<td><?php echo __('ragnarok', 'state')?></td>
			</tr>
		</thead>
		<tbody>
<?php if(empty($accounts)) : ?>
			<tr>
				<td class="ac-table-no-result"><?php echo __('ragnarok', 'no-accounts-registered')?></td>
			</tr>
<?php else : foreach($accounts as $acc) : ?>
			<tr>
				<?php $id = "acc-{$acc->id}"; ?>
				<td style="width: 20px;"><input type="radio" id="<?php echo $id?>" name="account_id" value="<?php echo $acc->id?>"></td>
				<td><label for="<?php echo $id?>" style="display: block; width: 100%; height: 100%;">
						<a href="<?php echo $acc->url()?>"><?php echo htmlspecialchars($acc->username)?></a>
					</label></td>
				<td><label for="<?php echo $id?>" style="display: block; width: 100%; height: 100%;"><?php echo $acc->state()?></label></td>
			</tr>
<?php endforeach; endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3">
						<input class="ac-button"
						       type="submit"
						       value="<?php echo __('ragnarok', 'purchase')?>"
							<?php echo (!count($accounts) || empty($cart->items) ? 'disabled' : '')?>>
				</td>
			</tr>
		</tfoot>
	</table>
</form>
</div>
