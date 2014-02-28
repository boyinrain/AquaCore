<?php
namespace Aqua\Ragnarok\Server;

use Aqua\Core\App;
use Aqua\Http\Uri;
use Aqua\Ragnarok\Cart;
use Aqua\Ragnarok\Character;
use Aqua\Ragnarok\Guild;
use Aqua\Ragnarok\Item;
use Aqua\Ragnarok\ItemData;
use Aqua\Ragnarok\Mob;
use Aqua\Ragnarok\Server;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\User\Account as UserAccount;
use Aqua\Ragnarok\Ragnarok;

class CharMap
{
	/**
	 * @var \Aqua\Ragnarok\Server
	 */
	public $server;
	/**
	 * @var string
	 */
	public $key;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var \Aqua\Ragnarok\Server\CharMapLog
	 */
	public $log;
	/**
	 * @var \PDO
	 */
	public $dbh;
	/**
	 * @var array
	 */
	public $dbSettings;
	/**
	 * @var string
	 */
	public $db;
	/**
	 * @var array
	 */
	public $tables = array();
	/**
	 * @var \Aqua\Http\URI
	 */
	public $uri;
	/**
	 * @var array
	 */
	public $settings;
	/**
	 * @var array
	 */
	public $woeSchedule;
	/**
	 * @var array
	 */
	public $cashShopCategories;
	/**
	 * @var array
	 */
	public $cache;
	/**
	 * @var \Aqua\Ragnarok\ItemData[]
	 */
	public $itemDb = array();
	/**
	 * @var \Aqua\Ragnarok\Mob[]
	 */
	public $mobDb = array();
	/**
	 * @var \Aqua\Ragnarok\Guild[]
	 */
	public $guilds = array();
	/**
	 * @var \Aqua\Ragnarok\Character[]
	 */
	public $characters = array();
	/**
	 * @var \Aqua\Ragnarok\Homunculus[]
	 */
	public $homunculus = array();

	const GET_ID   = 0;
	const GET_NAME = 1;

	const BASE_EXP  = 0;
	const JOB_EXP   = 1;
	const QUEST_EXP = 2;
	const MVP_EXP   = 3;

	const CACHE_AVG_PLAYERS_WEEKS = 2;

	/**
	 * @param string       $key
	 * @param \Aqua\Ragnarok\Server $server
	 * @param array  $settings
	 */
	public function __construct($key, Server &$server, array $settings)
	{
		$this->server     = &$server;
		$this->key        = $key;
		$this->name       = $settings['name'];
		$this->dbSettings = $settings['db'];
		$this->uri        = clone $this->server->uri;
		if($server->charmapCount > 1) {
			$this->uri->path[] = 's';
			$this->uri->path[] = $this->key;
		} else {
			$this->uri->path[] = 'server';
		}
		if(isset($settings['tables'])) { $this->tables = $settings['tables'] + $this->tables; }
		if(isset($settings['database_name'])) { $this->db = $settings['database_name']; }
		$this->log = new CharMapLog(
			$this,
			isset($settings['log_database_name']) ? $settings['log_database_name'] : null,
			$settings['log_db'],
			isset($settings['log_tables']) ? $settings['log_tables'] : array()
		);
	}

	/**
	 * @param array|string $settings
	 * @param string       $val
	 * @return bool
	 */
	public function setOption($settings, $val = '')
	{
		$count = 0;
		if(!is_array($settings)) {
			$settings = array( $settings => $val );
		}
		$sth = $this->connection()->prepare("
		REPLACE INTO {$this->table('ac_char_map_settings')} (`key`, val)
		VALUES (? , ?)
		");
		foreach($settings as $key => $val) {
			$sth->bindValue(1, $key, \PDO::PARAM_STR);
			$sth->bindValue(2, $val, \PDO::PARAM_LOB);
			$sth->execute();
			$this->settings[$key] = $val;
			$count+= $sth->rowCount();
		}
		if($count) {
			App::cache()->store("ro.{$this->server->key}.{$this->key}.settings", $this->settings);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public function getOption($key, $default = null)
	{
		$this->settings === null or $this->fetchSettings();
		return (isset($this->settings[$key]) ? $this->settings[$key] : $default);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function charSearch()
	{
		$columns = array(
			'id'                => 'c.char_id',
			'slot'              => 'c.char_num',
			'name'              => 'c.name',
			'class'             => 'c.class',
			'account_id'        => 'c.account_id',
			'base_level'        => 'c.base_level',
			'job_level'         => 'c.job_level',
			'base_experience'   => 'c.base_exp',
			'job_experience'    => 'c.job_exp',
			'zeny'              => 'c.zeny',
			'party_id'          => 'c.party_id',
			'guild_id'          => 'c.guild_id',
			'guild_name'        => 'g.name',
			'online'            => 'c.online',
			'last_map'          => 'c.last_map',
			'last_x'            => 'c.last_x',
			'last_y'            => 'c.last_y',
			'save_map'          => 'c.save_map',
			'save_x'            => 'c.save_x',
			'save_y'            => 'c.save_y',
			'fame'              => 'c.fame',
			'option'            => 'c.option',
			'karma'             => 'c.karma',
			'manner'            => 'c.manner',
			'str'               => 'c.str',
			'vit'               => 'c.vit',
			'dex'               => 'c.dex',
			'int'               => 'c.int',
			'luk'               => 'c.luk',
			'max_hp'            => 'c.max_hp',
			'max_sp'            => 'c.max_sp',
			'hp'                => 'c.hp',
			'sp'                => 'c.sp',
			'status_point'      => 'c.status_point',
			'skill_point'       => 'c.skill_point',
			'pet_id'            => 'c.pet_id',
			'homunculus_id'     => 'c.homun_id',
			'elemental_id'      => 'c.elemental_id',
			'partner_id'        => 'c.partner_id',
			'father_id'         => 'c.father',
			'mother_id'         => 'c.mother',
			'child_id'          => 'c.child',
			'cp_options'        => 'c.ac_options',
			'delete_date'       => 'c.delete_date'
		);
		return Query::search($this->connection())
			->columns($columns)
			->whereOptions(array(
				'guild_master' => 'g.guild_master',
			    'guild_level'  => 'g.guild_lv',
			    'guild_max_members'  => 'g.max_member',
			    'guild_online'  => 'g.connect_member',
			    'guild_average_level'  => 'g.average_lv',
			    'guild_experience'  => 'g.exp',
			    'guild_next_experience'  => 'g.next_exp',
			    'guild_skill_points'  => 'g.skill_point',
			    'guild_message1'  => 'g.mes1',
			    'guild_message2'  => 'g.mes2',
			    'guild_emblem_length'  => 'g.emblem_len',
			    'guild_emblem_id'  => 'g.emblem_id',
			    'guild_emblem_data'  => 'g.emblem_data',
			) + $columns)
			->from($this->table('char'), 'c')
			->leftJoin($this->table('guild'), 'g.guild_id = c.guild_id', 'g')
			->groupBy('c.char_id')
			->parser(array( $this, 'parseCharSql' ));
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function itemSearch()
	{
		$where = array(
			'id' => 'i.id',
			'identifier' => 'i.`name_english`',
			'name' => 'i.`name_japanese`',
			'type' => 'i.`type`',
			'range' => 'i.`range`',
			'slots' => 'i.`slots`',
			'job' => 'i.`equip_jobs`',
			'upper' => 'i.`equip_upper`',
			'gender' => 'i.`equip_genders`',
			'location' => 'i.`equip_locations`',
			'weapon_level' => 'i.`weapon_level`',
			'custom' => 'i.`custom_item`',
			'view' => 'i.`view`',
			'buying_price' => 'i.`price_buy`',
			'selling_price' => 'i.`price_sell`',
			'weight' => 'i.`weight`',
			'defence' => 'i.`defence`',
			'refineable' => 'i.`refineable`',
		);
		$columns = $where;
		$columns['script'] = 'i.`script`';
		$columns['equip_script'] = 'i.`equip_script`';
		$columns['unequip_script'] = 'i.`unequip_script`';
		$columns['description'] = 'i.`description`';
		$item_db2 = Query::search($this->connection())
		            ->columns(array( 'custom' => '1' ) + $columns)
		            ->whereOptions($where)
		            ->from($this->table('item_db2'), 'i');
		$item_db = Query::search($this->connection())
		           ->columns(array( 'custom' => '0' ) + $columns)
		           ->whereOptions($where)
			->from($this->table('item_db'), 'i')
			       ->union($item_db2, true);
		$search = Query::search($this->connection())
		        ->columns(array(
					'i.*',
					'shop_price' => 'cs.`price`',
					'shop_category' => 'cs.`category`',
					'cash_shop' => 'COUNT(cs.`item_id`)'
				))
		        ->whereOptions(array(
					'shop_price' => 'cs.price',
					'shop_category' => 'cs.category',
					'custom' => 'i.`custom`',
				))
		        ->havingOptions(array( 'cash_shop' => 'cash_shop' ))
				->from($item_db, 'i')
				->leftJoin($this->table('ac_cash_shop'), 'cs.item_id = i.id', 'cs')
				->groupBy('i.id')
				->parser(array( $this, 'parseItemDataSql' ));
		$where = $columns = array();
		switch($this->server->emulator) {
			case Server::EMULATOR_HERCULES:
				$where = $columns = array(
					'attack' => 'i.`atk`',
					'mattack' => 'i.`matk`',
					'equip_level_max' => 'i.`equip_level_max`',
					'equip_level_min' => 'i.`equip_level_min`'
				);
				break;
			case Server::EMULATOR_RATHENA:
				if($this->getOption('renewal')) {
					$where = array(
						'attack' => 'SUBSTR(i.`attack:matk`, 1, LOCATE(\':\', i.`attack:matk`) - 1)',
						'mattack' => 'SUBSTR(i.`attack:matk`, LOCATE(\':\', i.`attack:matk`) + 1)',
						'equip_level_max' => 'i.`equip_level`',
					);
					$columns = array(
							'atk:matk' => 'i.`attack:matk`',
							'equip_level_max' => 'i.`equip_level`',
							'equip_level_min' => '0'
						);
				} else {
					$where = array(
						'attack' => 'i.`attack`',
						'equip_level_max' => 'i.`equip_level`',
					);
					$columns = array(
							'attack' => 'i.`attack`',
							'mattack' => '0',
							'equip_level_max' => 'i.`equip_level`',
							'equip_level_min' => '0'
						);
				}
				break;
		}
		$item_db2->whereOptions($where)->columns($columns);
		$item_db->whereOptions($where)->columns($columns);
		$item_db2->where = &$search->where;
		$item_db->where  = &$search->where;
		return $search;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function itemShopSearch()
	{
		$where = array(
			'identifier' => 'i.`name_english`',
			'name' => 'i.`name_japanese`',
			'type' => 'i.`type`',
			'range' => 'i.`range`',
			'slots' => 'i.`slots`',
			'job' => 'i.`equip_jobs`',
			'upper' => 'i.`equip_upper`',
			'location' => 'i.`equip_locations`',
			'weapon_level' => 'i.`weapon_level`',
			'custom' => 'i.`custom_item`',
			'view' => 'i.`view`',
			'buying_price' => 'i.`price_buy`',
			'selling_price' => 'i.`price_sell`',
			'weight' => 'i.`weight`',
			'defence' => 'i.`defence`',
			'refineable' => 'i.`refineable`',
		);
		$columns = $where;
		$columns['id'] = 'i.id';
		$columns['script'] = 'i.`script`';
		$columns['equip_script'] = 'i.`equip_script`';
		$columns['unequip_script'] = 'i.`unequip_script`';
		$item_db2 = Query::search($this->connection())
			->columns(array( 'custom' => '1' ) + $columns)
			->whereOptions($where)
			->from($this->table('item_db2'), 'i');
		$item_db = Query::search($this->connection())
			->columns(array( 'custom' => '0' ) + $columns)
			->whereOptions($where)
			->from($this->table('item_db'), 'i')
			->union($item_db2, true);
		$search = Query::search($this->connection())
			->columns(array(
				'tmp_tbl.*',
				'shop_price' => 'cs.price',
				'shop_category' => 'cs.category',
				'shop_order' => 'cs.`order`',
			))
			->whereOptions(array(
				'id' => 'cs.item_id',
				'shop_price' => 'cs.price',
				'shop_category' => 'cs.category',
				'shop_order' => 'cs.`order`',
				'custom' => 'tmp_tbl.`custom`',
			))
			->from($this->table('ac_cash_shop'), 'cs')
			->innerJoin($item_db, 'tmp_tbl.id = cs.item_id', 'tmp_tbl')
			->groupBy('cs.id')
			->parser(array( $this, 'parseItemDataSql' ));
		$where = $columns = array();
		switch($this->server->emulator) {
			case Server::EMULATOR_HERCULES:
				$where = $columns = array(
					'attack' => 'i.`atk`',
					'mattack' => 'i.`matk`',
					'equip_level_max' => 'i.`equip_level_max`',
					'equip_level_min' => 'i.`equip_level_min`'
				);
				break;
			case Server::EMULATOR_RATHENA:
				if($this->getOption('renewal')) {
					$where = array(
						'attack' => 'SUBSTR(i.`attack:matk`, 1, LOCATE(\':\', i.`attack:matk`) - 1)',
						'mattack' => 'SUBSTR(i.`attack:matk`, LOCATE(\':\', i.`attack:matk`) + 1)',
						'equip_level_max' => 'i.`equip_level`',
					);
					$columns = array(
						'attack:matk' => 'i.`attack:matk`',
						'equip_level_max' => 'i.`equip_level`',
						'equip_level_min' => '0'
					);
				} else {
					$where = array(
						'attack' => 'i.`attack`',
						'equip_level_max' => 'i.`equip_level`',
					);
					$columns = array(
						'attack' => 'i.`attack`',
						'mattack' => '0',
						'equip_level_max' => 'i.`equip_level`',
						'equip_level_min' => '0'
					);
				}
				break;
		}
		$item_db2->whereOptions($where)->columns($columns);
		$item_db->whereOptions($where)->columns($columns);
		$item_db2->where = &$search->where;
		$item_db->where  = &$search->where;
		$item_db2->order = &$search->order;
		$item_db->order  = &$search->order;
		return $search;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function guildSearch()
	{
		$columns = array(
			'master' => 'g.guild_master',
			'level'  => 'g.guild_lv',
			'max_members'  => 'g.max_member',
			'online'  => 'g.connect_member',
			'average_level'  => 'g.average_lv',
			'experience'  => 'g.exp',
			'next_experience'  => 'g.next_exp',
			'skill_points'  => 'g.skill_point',
			'message1'  => 'g.mes1',
			'message2'  => 'g.mes2',
			'emblem_length'  => 'g.emblem_len',
			'emblem_id'  => 'g.emblem_id',
			'emblem_data'  => 'g.emblem_data',
		);
		return Query::search($this->connection())
			->columns(array(
				'member_count' => 'COUNT(gm.char_id)',
				'castle_count' => 'COUNT(gc.castle_id)',
			) + $columns)
			->whereOptions($columns)
			->havingOptions(array(
				                'member_count' => 'COUNT(gm.char_id)',
				                'castle_count' => 'COUNT(gc.castle_id)',
			                ))
			->from($this->table('guild'), 'g')
			->leftJoin($this->table('guild_castle'), 'g.guild_id = gc.guild_id', 'gc')
			->leftJoin($this->table('guild_member'), 'g.guild_id = gm.guild_id', 'gm')
			->groupBy('g.guild_id')
			->parser(array( $this, 'parseGuildSql' ));
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function mobSearch()
	{
		$columns = array(
			'id' => 'm.ID',
			'identifier' => 'm.kName',
			'name' => 'm.iName',
			'level' => 'm.LV',
			'hp' => 'm.HP',
			'sp' => 'm.SP',
			'base_exp' => 'm.EXP',
			'job_exp' => 'm.JEXP',
			'mvp_exp' => 'm.MEXP',
			'scale' => 'm.Scale',
			'race' => 'm.Race',
			'element' => 'm.Element',
			'attack_range' => 'm.Range1',
			'min_attack' => 'm.ATK1',
			'max_attack' => 'm.ATK2',
			'defence' => 'm.DEF',
			'mdefence' => 'm.MDEF',
			'str' => 'm.STR',
			'agi' => 'm.AGI',
			'vit' => 'm.VIT',
			'int' => 'm.INT',
			'dex' => 'm.DEX',
			'luk' => 'm.LUK',
			'skill_range' => 'm.Range2',
			'sight_range' => 'm.Range3',
			'speed' => 'm.Speed',
			'mode' => 'm.mode',
			'attack_delay' => 'm.aDelay',
			'attack_motion' => 'm.aMotion',
			'damage_motion' => 'm.dMotion',
			'card_id' => 'm.DropCardid',
			'card_rate' => 'm.DropCardper',
		);
		$where = $columns;
		$where['element'] = '(m.Element % 10)';
		$where['element_level'] = 'FLOOR(m.Element / 20)';
		$mob_db2 = Query::search($this->connection())
			->columns(array( 'custom' => '1' ) + $columns)
			->whereOptions($where)
			->from($this->table('mob_db2'), 'm');
		$mob_db = Query::search($this->connection())
			->columns(array( 'custom' => '0' ) + $columns)
			->whereOptions($where)
			->from($this->table('mob_db'), 'm')
			->union($mob_db2, true);
		$search = Query::search($this->connection())
			->columns(array( 'm.*' ))
			->whereOptions(array( 'custom' => 'm.custom' ))
			->from($mob_db, 'm')
			->groupBy('m.id')
			->parser(array( $this, 'parseMobSql' ));
		$mob_db2->where = &$search->where;
		$mob_db->where = &$search->where;
		$mob_db2->order = &$search->order;
		$mob_db->order = &$search->order;
		return $search;
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function storageSearch()
	{
		return $this->_inventorySearch()
			->columns(array( 'account_id' => 'x.account_id', 'storage_type' => Item::TYPE_STORAGE ))
			->whereOptions(array( 'account_id' => 'x.account_id' ))
			->from($this->table('storage'), 'x');
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function cartSearch()
	{
		return $this->_inventorySearch()
			->columns(array( 'char_id' => 'x.char_id', 'storage_type' => Item::TYPE_CART ))
			->whereOptions(array( 'char_id' => 'x.char_id' ))
			->from($this->table('cart_inventory'), 'x');
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public function inventorySearch()
	{
		return $this->_inventorySearch()
		    ->columns(array( 'char_id' => 'x.char_id', 'storage_type' => Item::TYPE_INVENTORY ))
			->whereOptions(array( 'char_id' => 'x.char_id' ))
			->from($this->table('inventory'), 'x');
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	protected function _inventorySearch()
	{
		return Query::search($this->connection())
			->columns(array(
					'identifier' => 'idb.identifier',
					'name' => 'idb.name',
					'type' => 'idb.type',
					'slots' => 'idb.slots',
					'custom' => 'idb.custom',
					'id' => 'x.id',
					'item_id' => 'x.nameid',
					'identify' => 'x.identify',
					'attribute' => 'x.attribute',
					'equip' => 'x.equip',
					'amount' => 'x.amount',
					'unique_id' => 'x.unique_id',
					'card0' => 'x.card0',
					'card1' => 'x.card1',
					'card2' => 'x.card2',
					'card3' => 'x.card3',
					'forger_id' => 'c.char_id',
					'forger_name' => 'c.name'
				))
			->whereOptions(array(
					'identifier' => 'idb.identifier',
					'name' => 'idb.name',
					'type' => 'idb.type',
					'slots' => 'idb.slots',
					'custom' => 'idb.custom',
					'id' => 'x.id',
					'item_id' => 'x.nameid',
					'identify' => 'x.identify',
					'attribute' => 'x.attribute',
					'equip' => 'x.equip',
					'amount' => 'x.amount',
					'unique_id' => 'x.unique_id',
					'card0' => 'x.card0',
					'card1' => 'x.card1',
					'card2' => 'x.card2',
					'card3' => 'x.card3',
				))
			->havingOptions(array(
					'forger_name' => 'forger_name',
					'forger_id' => 'forger_id'
				))
			->innerJoin(Query::select($this->connection())->columns(array(
							'id' => 'i.id',
							'identifier' => 'i.`name_english`',
							'name' => 'i.`name_japanese`',
							'slots' => 'i.`slots`',
							'type'  => 'i.`type`',
							'custom' => '0'
						))->from($this->table('item_db2'), 'i')->union(Query::select($this->connection())->columns(array(
								'id' => 'i.id',
								'identifier' => 'i.`name_english`',
								'name' => 'i.`name_japanese`',
								'slots' => 'i.`slots`',
								'type'  => 'i.`type`',
								'custom' => '1'
						))->from($this->table('item_db2'), 'i'), true), 'idb.id = x.nameid', 'idb')
			->leftJoin($this->table('char'), 'c.char_id = IF(x.card0 IN (254, 255), IF(x.card2 < 0, x.card2 + 65536, x.card2) | (x.card3 << 16), NULL)', 'c')
			->groupBy('x.id')
			->parser(array( $this, '_parseItemSql' ));
	}

	/**
	 * @param int|string $id
	 * @param string     $type "id" or "name"
	 * @return \Aqua\Ragnarok\Character|null
	 */
	public function character($id, $type = 'id')
	{
		if($type === 'id' && isset($this->characters[$id])) {
			return $this->characters[$id];
		}
		$select = Query::select($this->connection())
			->columns(array(
				          'id'                => 'c.char_id',
				          'slot'              => 'c.char_num',
				          'name'              => 'c.name',
				          'class'             => 'c.class',
				          'account_id'        => 'c.account_id',
				          'base_level'        => 'c.base_level',
				          'job_level'         => 'c.job_level',
				          'base_experience'   => 'c.base_exp',
				          'job_experience'    => 'c.job_exp',
				          'zeny'              => 'c.zeny',
				          'party_id'          => 'c.party_id',
				          'guild_id'          => 'c.guild_id',
				          'guild_name'        => 'g.guild_name',
				          'online'            => 'c.online',
				          'last_map'          => 'c.last_map',
				          'last_x'            => 'c.last_x',
				          'last_y'            => 'c.last_y',
				          'save_map'          => 'c.save_map',
				          'save_x'            => 'c.save_x',
				          'save_y'            => 'c.save_y',
				          'fame'              => 'c.fame',
				          'option'            => 'c.option',
				          'karma'             => 'c.karma',
				          'manner'            => 'c.manner',
				          'str'               => 'c.str',
				          'vit'               => 'c.vit',
				          'dex'               => 'c.dex',
				          'int'               => 'c.int',
				          'luk'               => 'c.luk',
				          'max_hp'            => 'c.max_hp',
				          'max_sp'            => 'c.max_sp',
				          'hp'                => 'c.hp',
				          'sp'                => 'c.sp',
				          'status_point'      => 'c.status_point',
				          'skill_point'       => 'c.skill_point',
				          'pet_id'            => 'c.pet_id',
				          'homunculus_id'     => 'c.homun_id',
				          'elemental_id'      => 'c.elemental_id',
				          'partner_id'        => 'c.partner_id',
				          'father_id'         => 'c.father',
				          'mother_id'         => 'c.mother',
				          'child_id'          => 'c.child',
				          'cp_options'        => 'c.ac_options',
				          'delete_date'       => 'c.delete_date'
			          ))
			->from($this->table('char'), 'c')
			->leftJoin($this->table('guild'), 'g')
			->limit(1)
			->parser(array( $this, 'parseCharSql' ));
		switch($type) {
			case 'id':
				$select->where(array( 'c.char_id' => $id ));
				break;
			case 'name':
				$select->where(array( 'c.name' => $id ));
				break;
			default:
				return null;
		}
		$select->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param int|string $id
	 * @param string $type "id" or "identifier"
	 * @return \Aqua\Ragnarok\ItemData|null
	 */
	public function item($id, $type = 'id')
	{
		if(!$id) {
			return null;
		} else if($type === 'id' && isset($this->itemDb[$id])) {
			return $this->itemDb[$id];
		}
		$columns = array(
			'id' => 'i.id',
			'identifier' => 'i.`name_english`',
			'name' => 'i.`name_japanese`',
			'type' => 'i.`type`',
			'range' => 'i.`range`',
			'slots' => 'i.`slots`',
			'job' => 'i.`equip_jobs`',
			'upper' => 'i.`equip_upper`',
			'gender' => 'i.`equip_genders`',
			'location' => 'i.`equip_locations`',
			'weapon_level' => 'i.`weapon_level`',
			'custom' => 'i.`custom_item`',
			'view' => 'i.`view`',
			'buying_price' => 'i.`price_buy`',
			'selling_price' => 'i.`price_sell`',
			'weight' => 'i.`weight`',
			'defence' => 'i.`defence`',
			'refineable' => 'i.`refineable`',
			'script' => 'i.`script`',
			'equip_script' => 'i.`equip_script`',
			'unequip_script' => 'i.`unequip_script`',
			'description' => 'i.`description`',
		);
		$item_db2 = Query::select($this->connection())
		                 ->columns(array( 'custom' => '1' ) + $columns)
		                 ->from($this->table('item_db2'), 'i');
		$item_db = Query::select($this->connection())
		                ->columns(array( 'custom' => '0' ) + $columns)
		                ->from($this->table('item_db'), 'i')
		                ->union($item_db2, true);
		$select = Query::select($this->connection())
		               ->columns(array(
			                         'i.*',
			                         'shop_price' => 'cs.`price`',
			                         'shop_category' => 'cs.`category`',
			                         'cash_shop' => 'COUNT(cs.`item_id`)'
		                         ))
		               ->from($item_db, 'i')
		               ->leftJoin($this->table('ac_cash_shop'), 'cs.item_id = i.id', 'cs')
		               ->groupBy('i.id')
						->limit(1)
		               ->parser(array( $this, 'parseItemDataSql' ));
		$columns = array();
		switch($this->server->emulator) {
			case Server::EMULATOR_HERCULES:
				$columns = array(
					'attack' => 'i.`atk`',
					'mattack' => 'i.`matk`',
					'equip_level_max' => 'i.`equip_level_max`',
					'equip_level_min' => 'i.`equip_level_min`'
				);
				break;
			case Server::EMULATOR_RATHENA:
				if($this->getOption('renewal')) {
					$columns = array(
						'attack:matk' => 'i.`attack:matk`',
						'equip_level_max' => 'i.`equip_level`',
						'equip_level_min' => '0'
					);
				} else {
					$columns = array(
						'attack' => 'i.`attack`',
						'mattack' => '0',
						'equip_level_max' => 'i.`equip_level`',
						'equip_level_min' => '0'
					);
				}
				break;
		}
		$item_db2->columns($columns);
		$item_db->columns($columns);
		switch($type) {
			case 'id':
				$item_db->where(array( 'i.id' => $id ));
				$item_db2->where(array( 'i.id' => $id ));
				break;
			case 'identifier':
				$item_db->where(array( 'i.name_english' => $id ));
				$item_db2->where(array( 'i.name_english' => $id ));
				break;
			default:
				return null;
		}
		$select->query();

		return ($select->valid() ? $select->current() : null);
	}

	/**
	 * @param int|string $id
	 * @param string $type "id" or "identifier"
	 * @return \Aqua\Ragnarok\Mob|null
	 */
	public function mob($id, $type = 'id')
	{
		if(!$id) {
			return null;
		} else if($type === 'id' && isset($this->mobDb[$id])) {
			return $this->mobDb[$id];
		}
		$columns = array(
			'id' => 'm.ID',
			'identifier' => 'm.kName',
			'name' => 'm.iName',
			'level' => 'm.LV',
			'hp' => 'm.HP',
			'sp' => 'm.SP',
			'base_exp' => 'm.EXP',
			'job_exp' => 'm.JEXP',
			'mvp_exp' => 'm.MEXP',
			'scale' => 'm.Scale',
			'race' => 'm.Race',
			'element' => 'm.Element',
			'attack_range' => 'm.Range1',
			'min_attack' => 'm.ATK1',
			'max_attack' => 'm.ATK2',
			'defence' => 'm.DEF',
			'mdefence' => 'm.MDEF',
			'str' => 'm.STR',
			'agi' => 'm.AGI',
			'vit' => 'm.VIT',
			'int' => 'm.INT',
			'dex' => 'm.DEX',
			'luk' => 'm.LUK',
			'skill_range' => 'm.Range2',
			'sight_range' => 'm.Range3',
			'speed' => 'm.Speed',
			'mode' => 'm.mode',
			'attack_delay' => 'm.aDelay',
			'attack_motion' => 'm.aMotion',
			'damage_motion' => 'm.dMotion',
			'card_id' => 'm.DropCardid',
			'card_rate' => 'm.DropCardper',
		);
		$mob_db2 = Query::select($this->connection())
			->columns(array( 'custom' => '1' ) + $columns)
			->from($this->table('mob_db2'), 'm');
		$mob_db = Query::select($this->connection())
			->columns(array( 'custom' => '0' ) + $columns)
			->from($this->table('mob_db'), 'm')
			->union($mob_db2, true)
			->parser(array( $this, 'parseMobSql' ))
			->limit(1);
		switch($type) {
			case 'id':
				$mob_db2->where(array( 'm.ID' => $id ));
				$mob_db->where(array( 'm.ID' => $id ));
				break;
			case 'identifier':
				$mob_db2->where(array( 'm.kName' => $id ));
				$mob_db->where(array( 'm.kName' => $id ));
				break;
			default:
				return null;
		}
		$mob_db->query();

		return ($mob_db->valid() ? $mob_db->current() : null);
	}

	/**
	 * @param int|string       $id
	 * @param string $type "id" or "name"
	 */
	public function guild($id, $type = 'id')
	{

	}

	/**
	 * @param int $id
	 * @param $len
	 * @param $data
	 * @return bool false if the guild doesn't exist, true otherwise
	 */
	public function emblem($id, &$len, &$data)
	{
		$sth = $this->connection()->prepare("
		SELECT emblem_len, emblem_data
		FROM {$this->table('guild')}
		WHERE guild_id = ? LIMIT 1
		");
		$sth->bindValue(1, $id, \PDO::PARAM_INT);
		$sth->execute();
		if($emblem_data = $sth->fetch(\PDO::FETCH_NUM)) {
			$len  = $emblem_data[0];
			$data = $emblem_data[1];
			return true;
		}
		return false;
	}

	/**
	 * Search monsters who drops the given item
	 * Example:
	 * <code>
	 * $charMap->whoDrops(12262, 1) // Who drops "Greatest Badge"
	 *          returns array(
	 *              1 => array(
	 *                  'id' => 1170,
	 *                  'name' => 'Sohee',
	 *                  'max_rate' => 3.5,
	 *                  'amount' => 1
	 *              ),
	 *              2 => array(
	 *                  'id' => 1277,
	 *                  'name' => 'Greatest General',
	 *                  'max_rate' => 3.0
	 *                  'amount' => 1
	 *              )
	 *          );
	 * </code>
	 *
	 * @param int $item_id ID of the item
	 * @param int $precision Number of decimal places
	 * @return array List of mobs found
	 */
	public function whoDrops($item_id, $precision = 3)
	{
		if(!($item = $this->item($item_id))) {
			return array();
		}
		$sth = $this->connection()->prepare("
		SELECT ID, iName, Mode,
		       DropCardid, DropCardper,
		       Drop1id, Drop1per,
		       Drop2id, Drop2per,
		       Drop3id, Drop3per,
		       Drop4id, Drop4per,
		       Drop5id, Drop5per,
		       Drop6id, Drop6per,
		       Drop7id, Drop7per,
		       Drop8id, Drop8per,
		       Drop9id, Drop9per,
		       MVP1id,  MVP1per,
		       MVP2id,  MVP2per,
		       MVP3id,  MVP3per
		FROM {$this->table('mob_db')}
		WHERE DropCardid = :id OR
		      Drop1id    = :id OR
		      Drop2id    = :id OR
		      Drop3id    = :id OR
		      Drop4id    = :id OR
		      Drop5id    = :id OR
		      Drop6id    = :id OR
		      Drop7id    = :id OR
		      Drop8id    = :id OR
		      Drop9id    = :id OR
		      MVP1id     = :id OR
		      MVP2id     = :id OR
		      MVP3id     = :id
		UNION ALL
		SELECT ID, iName, Mode,
		       DropCardid, DropCardper,
		       Drop1id, Drop1per,
		       Drop2id, Drop2per,
		       Drop3id, Drop3per,
		       Drop4id, Drop4per,
		       Drop5id, Drop5per,
		       Drop6id, Drop6per,
		       Drop7id, Drop7per,
		       Drop8id, Drop8per,
		       Drop9id, Drop9per,
		       MVP1id,  MVP1per,
		       MVP2id,  MVP2per,
		       MVP3id,  MVP3per
		FROM {$this->table('mob_db2')}
		WHERE DropCardid = :id OR
		      Drop1id    = :id OR
		      Drop2id    = :id OR
		      Drop3id    = :id OR
		      Drop4id    = :id OR
		      Drop5id    = :id OR
		      Drop6id    = :id OR
		      Drop7id    = :id OR
		      Drop8id    = :id OR
		      Drop9id    = :id OR
		      MVP1id     = :id OR
		      MVP2id     = :id OR
		      MVP3id     = :id
		");
		$sth->bindValue(':id', $item_id, \PDO::PARAM_INT);
		$sth->execute();
		$drops = array();
		while($res = $sth->fetch(\PDO::FETCH_NUM)) {
			$isBoss = (int)$res[3] & 32;
			$mob_data = array(
				'id' => (int)$res[0],
				'name' => $res[1],
				'max_rate' => 0,
				'amount' => 0,
			);
			$mvp = false;
			$rate = 0;
			for($i = 3; $i < 29; $i += 2) {
				if((int)$res[$i] === $item_id) {
					++$mob_data['amount'];
					if((int)$res[$i + 1] > $rate) {
						$rate = (int)$res[$i + 1];
						$mvp = ($i > 20);
					}
				}
			}
			if($mvp) {
				$mob_data['max_rate'] = $this->calcMvpDropRate($rate, $precision);
			} else if($isBoss) {
				$mob_data['max_rate'] = $this->calcBossDropRate($rate, $item->type, $precision);
			} else {
				$mob_data['max_rate'] = $this->calcDropRate($rate, $item->type, $precision);
			}
			$drops[] = $mob_data;
		}
		return $drops;
	}

	/**
	 * Get all drops form a monster
	 *
	 * @param int $mob_id
	 * @param int $precision
	 * @return array
	 */
	public function mobDrops($mob_id, $precision = 3)
	{
		$sth = $this->connection()->prepare("
		SELECT m.Mode,
		       m.DropCardid, m.DropCardper,
		       m.Drop1id, m.Drop1per,
		       m.Drop2id, m.Drop2per,
		       m.Drop3id, m.Drop3per,
		       m.Drop4id, m.Drop4per,
		       m.Drop5id, m.Drop5per,
		       m.Drop6id, m.Drop6per,
		       m.Drop7id, m.Drop7per,
		       m.Drop8id, m.Drop8per,
		       m.Drop9id, m.Drop9per,
		       m.MVP1id,  m.MVP1per,
		       m.MVP2id,  m.MVP2per,
		       m.MVP3id,  m.MVP3per
		FROM {$this->table('mob_db')} m
		WHERE m.ID = :id
		UNION ALL
		SELECT m.Mode,
		       m.DropCardid, m.DropCardper,
		       m.Drop1id, m.Drop1per,
		       m.Drop2id, m.Drop2per,
		       m.Drop3id, m.Drop3per,
		       m.Drop4id, m.Drop4per,
		       m.Drop5id, m.Drop5per,
		       m.Drop6id, m.Drop6per,
		       m.Drop7id, m.Drop7per,
		       m.Drop8id, m.Drop8per,
		       m.Drop9id, m.Drop9per,
		       m.MVP1id,  m.MVP1per,
		       m.MVP2id,  m.MVP2per,
		       m.MVP3id,  m.MVP3per
		FROM {$this->table('mob_db2')} m
		WHERE m.ID = :id
		LIMIT 1
		");
		$sth->bindValue(':id', $mob_id, \PDO::PARAM_INT);
		$sth->execute();
		$data = $sth->fetch(\PDO::FETCH_NUM);
		if(empty($data)) {
			return array();
		}
		$items = array();
		for($i = 0; $i < 13; ++$i) {
			$items[":{$i}drop"] = (int)$data[$i * 2 + 1];
		}
		$items = array_unique($items);
		$in = implode(', ', array_keys($items));
		$sth = $this->connection()->prepare("
		SELECT i.id, i.name_japanese, i.`type`
		FROM {$this->table('item_db')} i
		WHERE i.id IN ( $in )
		UNION ALL
		SELECT i.id, i.name_japanese, i.`type`
		FROM {$this->table('item_db2')} i
		WHERE i.id IN ( $in )
		");
		foreach($items as $key => $item) {
			$sth->bindValue($key, $item, \PDO::PARAM_INT);
		}
		$sth->execute();
		$drops = array();
		$item_data = array();
		foreach($sth->fetchAll(\PDO::FETCH_NUM) as $i) {
			$item_data[$i[0]] = array( $i[1], $i[2] );
		}
		$boss = (int)$data[0] & 32;
		for($i = 1; $i < 26; $i += 2) {
			if($data[$i]) {
				if($i > 21) {
					if(!isset($item_data[$data[$i]])) continue;
					$drops['mvp'][] = array(
						'id'   => (int)$data[$i],
						'name' => $item_data[$data[$i]][0],
						'type' => (int)$item_data[$data[$i]][1],
						'rate' => $this->calcMvpDropRate( (int)$data[($i + 1)], $precision )
					);
				} else {
					$d = array(
						'id'   => (int)$data[$i],
						'name' => $item_data[$data[$i]][0],
						'type' => (int)$item_data[$data[$i]][1],
						'rate' => ($boss ?
							$this->calcBossDropRate((int)$data[($i + 1)], (int)$item_data[$data[$i]][1], $precision ) :
							$this->calcDropRate((int)$data[($i + 1)], (int)$item_data[$data[$i]][1], $precision ))
					);
					if($i === 1) {
						$drops['card'] = $d;
					} else {
						$drops['normal'][] = $d;
					}
				}
			}
		}
		return $drops;
	}

	/**
	 * @param int                 $account_id
	 * @param \Aqua\Ragnarok\Cart $cart
	 * @return bool
	 */
	public function cashShopPurchase($account_id, Cart $cart)
	{
		$count = count($cart->items);
		if(!$count) {
			return false;
		}
		$ids = array_keys($cart->items);
		array_unshift($ids, AC_SEARCH_IN);
		$items = $this->itemShopSearch(array( 'id' => $ids ));
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('storage')} (account_id, nameid, amount, identify)
		VALUES (:account, :id, :amount, 1)
		");
		$sth2 = $this->connection()->prepare("
		UPDATE {$this->table('ac_cash_shop')}
		SET sold = sold + :amount
		WHERE item_id = :id
		");
		$total = 0;
		foreach($items as $item) {
			$cart->items['price'] = $item->shopPrice;
			$amount = $cart->items[$item->id]['amount'];
			$sth2->bindValue(':amount', $amount, \PDO::PARAM_INT);
			$sth2->bindValue(':id', $item->id, \PDO::PARAM_INT);
			$sth2->execute();
			$sth->bindValue(':account', $account_id, \PDO::PARAM_INT);
			$sth->bindValue(':id', $item->id, \PDO::PARAM_INT);
			switch($item->type) {
				case 4:
				case 5:
				case 7:
				case 8:
					$sth->bindValue(':amount', 1, \PDO::PARAM_INT);
					for($i = 0; $i < $amount; ++$i) {
						$sth->execute();
						$sth->closeCursor();
					}
					break;
				default:
					$sth->bindValue(':amount', $amount, \PDO::PARAM_INT);
					$sth->execute();
					$sth->closeCursor();
					break;
			}
			$total+= $amount * $item->shopPrice;
		}
		$this->log->logCashShopPurchase($account_id, $cart);
		return true;
	}

	/**
	 * @return array
	 */
	public function cashShopCategories()
	{
		$this->cashShopCategories !== null or $this->fetchCashShopCategories();
		return $this->cashShopCategories();
	}

	/**
	 * @param string $format
	 * @return string
	 */
	public function time($format = '%s')
	{
		$time = date_create('now');
		if(($timezone = $this->getOption('timezone'))) {
			$time->setTimeZone(new \DateTimeZone($timezone));
		}
		return gmstrftime($format, time() + $time->getOffset());
	}

	public function addWoeTime($name, array $castles, $end_day, $end_time, $start_day, $start_time)
	{
		$sth = $this->connection()->prepare("
		INSERT INTO {$this->table('ac_woe_schedule')} (name, start_day, start_time, end_day, end_time)
		VALUES (?, ?, ?, ?, ?)
		");
		$sth->bindValue(1, $name, \PDO::PARAM_STR);
		$sth->bindValue(2, $start_day + 1, \PDO::PARAM_INT);
		$sth->bindValue(3, $start_time, \PDO::PARAM_STR);
		$sth->bindValue(4, $end_day + 1, \PDO::PARAM_INT);
		$sth->bindValue(5, $end_time, \PDO::PARAM_STR);
		$sth->execute();
		$id = $this->connection()->lastInsertId();
		$this->woeSchedule[$id] = array(
			'name' => $name,
			'start_day' => $start_day,
			'start_time' => $start_time,
			'end_day' => $end_day,
			'end_time' => $end_time,
			'castles' => $castles
		);
		$this->editWoeCastles($id, $castles);
	}

	public function editWoeTime($id, $name, $start_day, $start_time, $end_day, $end_time)
	{
		$sth = $this->connection()->prepare("
		UPDATE {$this->table('ac_woe_schedule')}
		SET `name` = :name,
		    start_day = :sd,
		    start_time = :st,
		    end_day = :ed,
		    end_time = :et
		WHERE id = :id
		");
		$sth->bindValue(':name', $name, \PDO::PARAM_STR);
		$sth->bindValue(':sd', $start_day + 1, \PDO::PARAM_INT);
		$sth->bindValue(':st', $start_time, \PDO::PARAM_STR);
		$sth->bindValue(':ed', $end_day + 1, \PDO::PARAM_INT);
		$sth->bindValue(':et', $end_time, \PDO::PARAM_STR);
		$sth->execute();
		if($sth->rowCount()) {
			$this->woeSchedule[$id]['name'] = $name;
			$this->woeSchedule[$id]['start_day'] = $start_day;
			$this->woeSchedule[$id]['start_time'] = $start_time;
			$this->woeSchedule[$id]['end_day'] = $end_day;
			$this->woeSchedule[$id]['end_time'] = $end_time;
			App::cache()->store("ro.{$this->server->key}.{$this->key}.woe-schedule", $this->woeSchedule);
			return true;
		} else {
			return false;
		}
	}

	public function removeWoeTime($id)
	{
		$sth = $this->connection()->prepare("DELETE FROM {$this->table('ac_woe_schedule')} WHERE id = ?");
		$sth->bindValue(1, $id, \PDO::PARAM_INT);
		$sth->execute();
		if($sth->rowCount()) {
			unset($this->woeSchedule[$id]);
			App::cache()->store("ro.{$this->server->key}.{$this->key}.woe-schedule", $this->woeSchedule);
			return true;
		} else {
			return false;
		}
	}

	public function editWoeCastles($id, array $castles)
	{
		$this->woeSchedule !== null or $this->fetchWoeSchedule();
		if(!isset($this->woeSchedule[$id])) {
			return false;
		}
		$sth = $this->connection()->prepare("DELETE FROM {$this->table('ac_woe_castles')} WHERE shecule_id = ?");
		$sth->bindValue(1, $id, \PDO::PARAM_INT);
		$sth = $this->connection()->prepare("REPLACE INTO {$this->table('ac_woe_castles')} (schedule_id, castle) VALUES (? ,?)");
		$this->woeSchedule[$id]['castles'] = array();
		foreach($castles as $castle) {
			$sth->bindValue(1, $id, \PDO::PARAM_INT);
			$sth->bindValue(2, $castle, \PDO::PARAM_INT);
			$sth->execute();
			$this->woeSchedule[$id]['castles'][] = $castle;
		}
		App::cache()->store("ro.{$this->server->key}.{$this->key}.woe-schedule", $this->woeSchedule);
		return true;
	}

	/**
	 * Check whether WoE is active
	 *
	 * @param $ids
	 * @return bool
	 */
	public function woe(&$ids = null)
	{
		$this->woeSchedule !== null or $this->fetchWoeSchedule();
		$ids = array();
		$now = (int)$this->time();
		$weeks = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		foreach($this->woeSchedule as $id => &$woe) {
			$start = strtotime("{$weeks[$woe['start_day']]} {$woe['start_time']}");
			$end = strtotime("{$weeks[$woe['end_day']]} {$woe['end_time']}");
			if($now > $start && $now < $end) {
				$ids[] = $id;
			}
		}
		return (bool)count($ids);
	}

	/**
	 * @param $char
	 * @param $map
	 */
	public function serverStatus(&$char, &$map)
	{
		if(($status = App::cache()->fetch("ro.{$this->server->key}.{$this->key}.server-status", null)) !== null) {
			list($char, $map) = $status;
			return;
		}
		$char = ac_server_status($this->getOption('char-ip'), (int)$this->getOption('char-port'), (int)$this->getOption('status-timeout'));
		$map = ac_server_status($this->getOption('map-ip'), (int)$this->getOption('map-port'), (int)$this->getOption('status-timeout'));
		App::cache()->store("ro.{$this->server->key}.{$this->key}.server-status", array( $char, $map ), (int)$this->getOption('status-ttl', 300));
	}

	/**
	 * @param int      $start
	 * @param int|null $end
	 * @param int|null $cache
	 */
	public function onlineStats($start, $end = null, $cache = null)
	{
	}

	/**
	 * @param array $options
	 * @return string
	 */
	public function url(array $options = array())
	{
		return $this->uri->url($options);
	}

	/**
	 * @return \PDO
	 */
	public function connection()
	{
		if(!$this->dbh) {
			$this->dbh = ac_mysql_connection($this->dbSettings);
		}
		return $this->dbh;
	}

	/**
	 * @param string $table
	 * @return string
	 */
	public function table($table)
	{
		$tbl = isset($this->tables[$table]) ? $this->tables[$table] : $table;
		return ($this->db ? "`{$this->db}`.`{$tbl}`" : "`{$tbl}`");
	}

	/**
	 * Parse data from item db
	 *
	 * @param array $data
	 * @access protected
	 * @return \Aqua\Ragnarok\ItemData
	 */
	public function parseItemDataSql(array $data)
	{
		if(isset($this->itemDb[$data['id']])) {
			$item = $this->itemDb[$data['id']];
		} else {
			$item = new ItemData;
		}
		$item->id            = (int)$data['id'];
		$item->enName        = $data['identifier'];
		$item->jpName        = stripslashes($data['name']);
		$item->type          = (int)$data['type'];
		$item->buyingPrice   = (int)$data['buying_price'];
		$item->sellingPrice  = (int)$data['selling_price'];
		$item->weight        = (float)$data['weight'] / 10;
		$item->defence       = (int)$data['defence'];
		$item->range         = (int)$data['range'];
		$item->slots         = (int)$data['slots'];
		$item->equipJob      = (int)$data['job'];
		$item->equipUpper    = (int)$data['upper'];
		$item->equipGender   = (int)$data['gender'];
		$item->equipLocation = (int)$data['location'];
		$item->equipLevelMin = (int)$data['equip_level_min'];
		$item->equipLevelMax = (int)$data['equip_level_max'];
		$item->weaponLevel   = (int)$data['weapon_level'];
		$item->look          = (int)$data['view'];
		$item->refineable    = (int)$data['refineable'];
		$item->custom        = (bool)$data['custom'];
		$item->description   = $data['description'];
		$item->scriptUse     = $data['script'];
		$item->scriptEquip   = $data['equip_script'];
		$item->scriptUnequip = $data['unequip_script'];
		if(isset($data['atk:matk'])) {
			$atk = explode(':', $item['atk:matk'], 2);
			if(count($atk) > 0) $item->attack = (int)$atk[0];
			if(count($atk) > 1) $item->mattack = (int)$atk[1];
		} else {
			$item->attack = (int)$data['attack'];
			if(isset($data['mattack'])) $item->mattack = (int)$data['mattack'];
		}
		if($data['shop_price'] !== null && $data['shop_category'] !== null) {
			$item->shopPrice      = (int)$data['shop_price'];
			$item->shopCategoryId = $data['shop_category'];
			$item->inCashShop     = true;
		}
		return $item;
	}

	/**
	 * Parse data from inventory/storage/cart
	 *
	 * @param array $data
	 * @return \Aqua\Ragnarok\Item
	 */
	public function parseItemSql(array $data)
	{
		$item = new Item;
		$item->charmap    = &$this;
		$item->type       = (int)$data['storage_type'];
		$item->id         = (int)$data['id'];
		$item->itemId     = (int)$data['item_id'];
		$item->itemType   = (int)$data['type'];
		$item->slots      = (int)$data['slots'];
		$item->name       = stripslashes($data['name']);
		$item->character  = $data['forger_name'];
		$item->charId     = ($data['forger_id'] ? (int)$data['forger_id'] : null);
		$item->attribute  = (int)$data['attribute'];
		$item->uniqueId   = (int)$data['unique_id'];
		$item->refine     = (int)$data['refine'];
		$item->equip      = (int)$data['equip'];
		$item->amount     = (int)$data['amount'];
		$item->identified = (bool)$data['identify'];
		$item->cards[0]   = (int)$data['card0_id'];
		$item->cards[1]   = (int)$data['card1_id'];
		$item->cards[2]   = (int)$data['card2_id'];
		$item->cards[3]   = (int)$data['card3_id'];
		unset($data['id']);
		unset($data['storage_type']);
		unset($data['forger_name']);
		unset($data['forger_id']);
		unset($data['slots']);
		unset($data['name']);
		unset($data['type']);
		unset($data['attribute']);
		unset($data['unique_id']);
		unset($data['equip']);
		unset($data['identify']);
		unset($data['refine']);
		unset($data['amount']);
		unset($data['card0_id']);
		unset($data['card1_id']);
		unset($data['card2_id']);
		unset($data['card3_id']);
		$item->data = $data;
		return $item;
	}

	/**
	 * @param array $data
	 * @return \Aqua\Ragnarok\Mob
	 */
	public function parseMobSql(array $data)
	{
		if(isset($this->mobDb[$data['id']])) {
			$mob = $this->mobDb[$data['id']];
		} else {
			$mob = new Mob;
		}
		$mob->id            = (int)$data['id'];
		$mob->kName         = $data['identifier'];
		$mob->iName         = stripslashes($data['name']);
		$mob->level         = (int)$data['level'];
		$mob->hp            = (int)$data['hp'];
		$mob->sp            = (int)$data['sp'];
		$mob->attackRange   = (int)$data['attack_range'];
		$mob->minAttack     = (int)$data['min_attack'];
		$mob->maxAttack     = (int)$data['max_attack'];
		$mob->defence       = (int)$data['defence'];
		$mob->mDefence      = (int)$data['mdefence'];
		$mob->strength      = (int)$data['str'];
		$mob->agility       = (int)$data['agi'];
		$mob->vitality      = (int)$data['vit'];
		$mob->intelligence  = (int)$data['int'];
		$mob->dexterity     = (int)$data['dex'];
		$mob->skillRange    = (int)$data['skill_range'];
		$mob->sight         = (int)$data['sight_range'];
		$mob->size          = (int)$data['scale'];
		$mob->race          = (int)$data['race'];
		$mob->element       = (int)$data['element'];
		$mob->mode          = (int)$data['mode'];
		$mob->cardId        = (int)$data['card_id'];
		$mob->cardDropRate  = (int)$data['card_rate'];
		$mob->speed         = (int)$data['speed'];
		$mob->aDelay        = (int)$data['attack_delay'];
		$mob->aMotion       = (int)$data['attack_motion'];
		$mob->dMotion       = (int)$data['damage_motion'];
		$mob->baseExp       = $this->calcExperience((int)$data['base_exp'], self::BASE_EXP);
		$mob->jobExp        = $this->calcExperience((int)$data['job_exp'], self::JOB_EXP);
		$mob->mvpExp        = $this->calcExperience((int)$data['mvp_exp'], self::MVP_EXP);
		$mob->custom        = (bool)$data['custom'];
		return $mob;
	}

	/**
	 * Parse character data from sql
	 *
	 * @param array $data
	 * @access protected
	 * @return \Aqua\Ragnarok\Character
	 */
	public function parseCharSql($data)
	{
		if(isset($this->characters[$data['id']])) {
			$char = $this->characters[$data['id']];
		} else {
			$char = new Character;
		}
		$char->charmap      = &$this;
		$char->id           = (int)$data['id'];
		$char->accountId    = (int)$data['account_id'];
		$char->slot         = (int)$data['slot'];
		$char->name         = $data['name'];
		$char->class        = (int)$data['class'];
		$char->baseLevel    = (int)$data['base_level'];
		$char->jobLevel     = (int)$data['job_level'];
		$char->baseExp      = (int)$data['base_experience'];
		$char->jobExp       = (int)$data['job_experience'];
		$char->zeny         = (int)$data['zeny'];
		$char->karma        = (int)$data['karma'];
		$char->manner       = (int)$data['manner'];
		$char->partyId      = (int)$data['party_id'];
		$char->guildId      = (int)$data['guild_id'];
		$char->guildName    = $data['guild_name'];
		$char->homunculusId = (int)$data['homunculus_id'];
		$char->lastMap      = basename($data['last_map'], '.gat');
		$char->lastX        = (int)$data['last_x'];
		$char->lastY        = (int)$data['last_y'];
		$char->saveMap      = basename($data['save_map'], '.gat');
		$char->saveX        = (int)$data['save_x'];
		$char->saveY        = (int)$data['save_y'];
		$char->partnerId    = (int)$data['partner_id'];
		$char->fatherId     = (int)$data['father_id'];
		$char->motherId     = (int)$data['mother_id'];
		$char->childId      = (int)$data['child_id'];
		$char->fame         = (int)$data['fame'];
		$char->online       = (bool)$data['online'];
		$char->options      = (int)$data['cp_options'];

		return $char;
	}

	/**
	 * Parse guild data from sql
	 *
	 * @param array $data
	 * @access protected
	 * @return \Aqua\Ragnarok\Guild
	 */
	protected function _parseGuildSql($data)
	{
		if(isset($this->guilds[$data['id']])) {
			$guild = $this->guilds[$data['id']];
		} else {
			$guild = new Guild;
		}
		$guild->charmap       = &$this;
		$guild->id            = (int)$data['id'];
		$guild->name          = $data['name'];
		$guild->level         = (int)$data['level'];
		$guild->experience    = (int)$data['experience'];
		$guild->castleCount   = (int)$data['castle_count'];
		$guild->leaderId      = (int)$data['master_id'];
		$guild->leaderName    = $data['master_name'];
		$guild->averageLevel  = (int)$data['average_level'];
		$guild->memberCount   = (int)$data['member_count'];
		$guild->memberLimit   = (int)$data['member_limit'];
		return $guild;
	}

	/**
	 * @param int    $original_rate
	 * @param int    $type
	 * @param int  $precision
	 * @return float
	 */
	public function calcDropRate($original_rate, $type, $precision = 3)
	{
		switch($type) {
			// Healing
			case 0:
				$modifier = $this->getOption('rate.item-heal', 100);
				$min = $this->getOption('rate.item-heal-min', 1);
				$max = $this->getOption('rate.item-heal-max', 1000);
				break;
			case 2:
			case 12:
			case 11:
				$modifier = $this->getOption('rate.item-use', 100);
				$min = $this->getOption('rate.item-use-min', 1);
				$max = $this->getOption('rate.item-use-max', 1000);
				break;
			// Misc
			case 3:
			case 7:
			case 10:
				$modifier = $this->getOption('rate.item-common', 100);
				$min = $this->getOption('rate.item-common-min', 1);
				$max = $this->getOption('rate.item-common-max', 1000);
				break;
			// Equipment
			case 4:
			case 5:
			case 8:
				$modifier = $this->getOption('rate.item-equip', 100);
				$min = $this->getOption('rate.item-equip-min', 1);
				$max = $this->getOption('rate.item-equip-max', 1000);
				break;
			// Card
			case 6:
				$modifier = $this->getOption('rate.item-card', 100);
				$min = $this->getOption('rate.item-card-min', 1);
				$max = $this->getOption('rate.item-card-max', 1000);
				break;
			default: return 0;
		}
		return $this->_calcDropRate($original_rate, (int)$modifier, (int)$min, (int)$max, $precision);
	}

	/**
	 * @param int    $original_rate
	 * @param int    $type
	 * @param int $precision
	 * @return float
	 */
	public function calcBossDropRate($original_rate, $type, $precision = 3)
	{
		switch($type) {
			// Healing
			case 0:
				$modifier = $this->getOption('rate.item-heal-boss', 100);
				$min = $this->getOption('rate.item-heal-min', 1);
				$max = $this->getOption('rate.item-heal-max', 1000);
				break;
			case 2:
			case 12:
			case 11:
				$modifier = $this->getOption('rate.item-use-boss', 100);
				$min = $this->getOption('rate.item-use-min', 1);
				$max = $this->getOption('rate.item-use-max', 1000);
				break;
			// Misc
			case 3:
			case 7:
			case 10:
				$modifier = $this->getOption('rate.item-common-boss', 100);
				$min = $this->getOption('rate.item-common-min', 1);
				$max = $this->getOption('rate.item-common-max', 1000);
				break;
			// Equipment
			case 4:
			case 5:
			case 8:
				$modifier = $this->getOption('rate.item-equip-boss', 100);
				$min = $this->getOption('rate.item-equip-min', 1);
				$max = $this->getOption('rate.item-equip-max', 1000);
				break;
			// Card
			case 6:
				$modifier = $this->getOption('rate.item-card-boss', 100);
				$min = $this->getOption('rate.item-card-min', 1);
				$max = $this->getOption('rate.item-card-max', 1000);
				break;
			default: return 0;
		}
		return $this->_calcDropRate($original_rate, (int)$modifier, (int)$min, (int)$max, $precision);
	}

	/**
	 * @param int $original_rate
	 * @param int $precision
	 * @return float
	 */
	public function calcMvpDropRate($original_rate, $precision = 3)
	{
		return $this->_calcDropRate(
			$original_rate,
			(int)$this->getOption('rate.item-mvp', 100),
			(int)$this->getOption('rate.item-mvp-min', 1),
			(int)$this->getOption('rate.item-mvp-max', 1000),
			$precision
		);
	}

	/**
	 * @param int $original_rate
	 * @param int $modifier
	 * @param int $min
	 * @param int $max
	 * @param int $precision
	 * @return float
	 */
	protected function _calcDropRate($original_rate, $modifier, $min, $max, $precision)
	{
		if($this->getOption('logarithmic-drops') && $modifier > 0 && $modifier != 100) {
			$rate = $original_rate * pow(5 - log10($original_rate), (log($modifier/100) / log(5))) + 5;
		} else {
			$rate = $original_rate / 100 * $modifier;
		}
		$rate = max($min, min($rate, $max));
		return round((float)$rate / 100, $precision);
	}

	/**
	 * @param int $original_rate
	 * @param int $type
	 * @return float
	 */
	public function calcExperience($original_rate, $type)
	{
		switch($type) {
			case self::BASE_EXP:  $modifier = $this->getOption('rate.base-exp', 100); break;
			case self::JOB_EXP:   $modifier = $this->getOption('rate.job-exp', 100); break;
			case self::QUEST_EXP: $modifier = $this->getOption('rate.quest-exp', 100); break;
			case self::MVP_EXP:   $modifier = $this->getOption('rate.mvp-exp', 100); break;
			default: return $original_rate;
		}
		return ($original_rate / 100 * (int)$modifier);
	}

	/**
	 * @param bool $rebuild
	 * @return array
	 */
	public function fetchSettings($rebuild = false)
	{
		if($rebuild || !($this->settings = App::cache()->fetch("ro.{$this->server->key}.{$this->key}.settings", false))) {
			$this->settings = array();
			$sth = $this->connection()->query("
			SELECT `key`, val
			FROM {$this->table('ac_char_map_settings')}
			");
			$sth->execute();
			while($data = $sth->fetch(\PDO::FETCH_NUM)) {
				$this->settings[$data[0]] = $data[1];
			}
			App::cache()->store("ro.{$this->server->key}.{$this->key}.settings", $this->settings);
		}
		return $this->settings;
	}

	/**
	 * @param bool $rebuild
	 * @return array
	 */
	public function fetchWoeSchedule($rebuild = false)
	{
		if($rebuild || !($this->woeSchedule = App::cache()->fetch("ro.{$this->server->key}.{$this->key}.woe-schedule", false))) {
			$this->woeSchedule = array();
			$sth = $this->connection()->query("
			SELECT id,
			       `name`,
			       (start_day - 1) AS `start`,
			       start_time,
			       (end_day - 1) AS `end`,
			       end_time
			FROM {$this->table('ac_woe_schedule')}
			ORDER BY start_day, start_time
			");
			while($data = $sth->fetch(\PDO::FETCH_NUM)) {
				$schedule = array();
				$schedule['name'] = $data[1];
				$schedule['start_day'] = (int)$data[2];
				$schedule['start_time'] = $data[3];
				$schedule['end_day'] = (int)$data[4];
				$schedule['end_time'] = $data[5];
				$schedule['castles'] = array();
				$this->woeSchedule[$data[0]] = $schedule;
			}
			$sth = App::connection()->query("SELECT schedule_id, castle FROM {$this->table('ac_woe_castles')}");
			while($data = $sth->fetch(\PDO::FETCH_NUM)) {
				if(!isset($this->woeSchedule[$data[0]])) continue;
				$this->woeSchedule[$data[0]]['castles'][] = (int)$data[1];
			}
			App::cache()->store("ro.{$this->server->key}.{$this->key}.woe-schedule", $this->woeSchedule);
		}
		return $this->woeSchedule;
	}

	/**
	 * @param bool $rebuild
	 * @return array
	 */
	public function fetchCashShopCategories($rebuild = false)
	{
		if($rebuild || !($this->cashShopCategories = App::cache()->fetch("ro.{$this->server->key}.{$this->key}.shop-categories", false))) {
			$sth = $this->connection()->query("
			SELECT category, COUNT(1)
			FROM {$this->table('ac_cash_shop')}
			GROUP BY category
			");
			$this->cashShopCategories = array();
			while($res = $sth->fetch(\PDO::FETCH_NUM)) {
				$this->cashShopCategories[(string)$res[0]] = array(
					'name'  =>
					'count' => (int)$res[1]
				);
			}
			App::cache()->store("ro.{$this->server->key}.{$this->key}.shop-categories", $this->cashShopCategories);
		}
		return $this->cashShopCategories;
	}

	/**
	 * @param string|null $name
	 * @param bool $rebuild
	 * @return mixed
	 */
	public function fetchCache($name = null, $rebuild = false)
	{
		if($rebuild || !($this->cache = App::cache()->fetch("ro.{$this->server->key}.{$this->key}.cache", false))) {
			$select = Query::select($this->connection())
				->columns(array('class' => '`class`', 'count' => 'COUNT(1)'))
				->setColumnType(array( 'class' => 'integer', 'count' => 'integer' ))
				->from($this->table('char'))
				->groupBy('`class`')
				->order(array( '`class`' => 'ASC' ))
				->query();
			$this->cache['class_population'] = array();
			foreach($select as $row) {
				$this->cache['class_population'][$row['class']] = $row['count'];
			}
			$select->from($this->table('homunculus'))->query();
			$this->cache['homunculus_population'] = array();
			foreach($select as $row) {
				$this->cache['homunculus_population'][$row['class']] = $row['count'];
			}
			$select = Query::select($this->connection())
				->columns(array(
						'players' => 'MAX(players)',
						'date' => 'UNIX_TIMESTAMP(`date`)',
						'key' => "'all_time_player_peak'"
					))
				->from($this->table('ac_online_stats'));
			$this_month = clone $select;
			$last_month = clone $select;
			$this_month
				->columns(array( 'key' => "'this_month_player_peak'" ))
				->where(array(
					'date' => array( Search::SEARCH_HIGHER, date('Y:m:d H:i:s', strtotime('first day of this month midnight')) )
				));
			$last_month
				->columns(array( 'key' => "'last_month_player_peak'" ))
				->where(array(
					'date' => array(
						Search::SEARCH_BETWEEN,
						date('Y:m:d H:i:s', strtotime('first day of this month midnight')),
						date('Y:m:d H:i:s', strtotime('first day of last month midnight'))
					)
				));
			$select
				->union($this_month, true)
				->union($last_month, true)
			    ->setColumnType(array( 'players' => 'integer', 'date' => 'integer' ))
				->query();
			foreach($select as $row) {
				$this->cache[$row['key']] = array( 'count' => $row['players'], 'date' => $row['date'] );
			}
			$this->cache['char_count'] = (int)$this->connection()->query("SELECT COUNT(1) FROM {$this->table('char')}")->fetch(\PDO::FETCH_COLUMN, 0);
			$this->cache['party_count'] = (int)$this->connection()->query("SELECT COUNT(1) FROM {$this->table('party')}")->fetch(\PDO::FETCH_COLUMN, 0);
			$this->cache['guild_count'] = (int)$this->connection()->query("SELECT COUNT(1) FROM {$this->table('guild')}")->fetch(\PDO::FETCH_COLUMN, 0);
			$this->cache['homunculus_count'] = (int)$this->connection()->query("SELECT COUNT(1) FROM {$this->table('homunculus')}")->fetch(\PDO::FETCH_COLUMN, 0);
			$this->cache['online'] = (int)$this->connection()->query("SELECT COUNT(1) FROM {$this->table('char')} WHERE online = 1")->fetch(\PDO::FETCH_COLUMN, 0);
			App::cache()->store("ro.{$this->server->key}.{$this->key}.cache", $this->cache, 300);
		}
		if(!$name) {
			return $this->cache;
		} else if(array_key_exists($name, $this->cache)) {
			return $this->cache[$name];
		} else {
			return null;
		}
	}

	/**
	 * @param string|null $name
	 */
	public function flushCache($name = null)
	{
		if(!$name || $name === 'settings') App::cache()->delete("ro.{$this->server->key}.{$this->key}.settings");
		if(!$name || $name === 'woe-schedule') App::cache()->delete("ro.{$this->server->key}.{$this->key}.woe-schedule");
		if(!$name || $name === 'shop-categories') App::cache()->delete("ro.{$this->server->key}.{$this->key}.shop-categories");
		if(!$name || $name === 'server-status') App::cache()->delete("ro.{$this->server->key}.{$this->key}.shop-categories");
		if(!$name || $name === 'cache') App::cache()->delete("ro.{$this->server->key}.{$this->key}.cache");
	}
}