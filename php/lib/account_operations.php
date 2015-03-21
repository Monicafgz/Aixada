<?php
require_once(__ROOT__ . "local_config/config.php");
require_once(__ROOT__ . "php/inc/database.php");
require_once(__ROOT__ . "php/utilities/general.php");
require_once(__ROOT__ . 'php/lib/account_operations.config.php');

class account_operations {
    protected $cfg_accounts;
    protected $cfg_use_providers;
    
    public function __construct ($lang='') { // TODO: Use a lang other than User. 
        $this->cfg_accounts = get_config('accounts', array());
        $this->cfg_use_providers = isset($this->cfg_accounts['use_providers']) && 
				$this->cfg_accounts['use_providers'];
	}
    
    // --------------------------------------------
    // READ
    // --------------------------------------------
    
    public function latest_movements_rs(
                            $limit, $account_types, $show_uf, $show_providers) {
        $sql = array();
        $db = DBWrap::get_instance();
        if ($account_types) {
            array_push($sql, "(
                select a.id, a.account_id, time(a.ts) as time, a.ts,
                    a.quantity, balance,
                    p.description as method,
                    c.name as currency, 
                    ad.description as account_name,
                    -- 'uf_id' use for reasons of compatibility
                    concat('A',account_id) as uf_id
                from aixada_account a
                join (
                    aixada_account_desc ad,
                    aixada_currency c,
                    aixada_payment_method p
                )
                on 
                    a.currency_id = c.id
                    and a.payment_method_id = p.id
                    and a.account_id = -ad.id
                where account_id < 1000
                and ad.account_type in(".implode(',', $account_types).") )");
        }
        if ($show_uf) {
            array_push($sql, "(
                select a.id, a.account_id, time(a.ts) as time, a.ts,
                    a.quantity, balance,
                    p.description as method,
                    c.name as currency, 
                    concat(uf.name,'(',uf.id,')') as account_name,
                    -- 'uf_id' use for reasons of compatibility
                    concat(uf.id,' ',uf.name) as uf_id 
                from aixada_account a
                left join (
                    aixada_currency c,
                    aixada_payment_method p,
                    aixada_uf uf
                )
                on 
                    a.currency_id = c.id
                    and a.payment_method_id = p.id
                    and a.account_id - 1000 = uf.id
                where account_id between 1000 and 1999)");
        }
        if ($show_providers && $this->cfg_use_providers) {
            array_push($sql, "(
                select a.id, a.account_id, time(a.ts) as time, a.ts,
                    a.quantity, balance,
                    p.description as method,
                    c.name as currency, 
                    concat(prv.name,'(',prv.id,')') as account_name,
                    -- 'uf_id' use for reasons of compatibility                    
                    concat(prv.name,'(',prv.id,')') as uf_id
                from aixada_account a
                left join (
                    aixada_currency c,
                    aixada_payment_method p,                
                    aixada_provider prv
                )
                on 
                    a.currency_id = c.id
                    and a.payment_method_id = p.id
                    and a.account_id - 2000 = prv.id
                where account_id between 2000 and 2999)");
        }
        if (count($sql) != 0) {
            $sql2 = implode($sql, ' union ').
                                   " order by ts desc, id desc limit {$limit};";
            return $db->Execute($sql2);
        } else	{
			return null;
		}
    }
    
    /**
     * 
     * Retrieves list of accounts
     * @param boolean $all if set to true, list active and non-active accounts. when set to false, list only active UFs
     */
    public function get_accounts_XML($all=0, $account_types, $show_uf, $show_providers) {
        // Load config
        $cfg_accounts = get_config('accounts', array());

        // start XML
        $strXML = '';
        
        // Specific accounts
        if ($account_types) {
            $sqlStr = "SELECT -id id, description name
				FROM aixada_account_desc ad
				where account_type in(".implode(',', $account_types).")";
            $sqlStr .= $all ? "" :" and active=1";
            $sqlStr .= " order by ad.id";
            $strXML .= query_to_XML($sqlStr);
        }    
        // UF accounts
        if ($show_uf) {
            if ($show_uf == 2) {
                $strXML .= array_to_XML(array(
                    'id'    => 1000,
                    'name'  => i18n('mon_all_active_uf')
                ));            
            }
            $sqlStr = "SELECT id+1000 id, concat(id,' ',name) name FROM aixada_uf";
            $sqlStr .= $all ? "" :" where active=1";
            $sqlStr .= " order by id";
            $strXML .= query_to_XML($sqlStr);
        }
        // Providers
        if ($show_providers && $this->cfg_use_providers) {
            $sqlStr = "SELECT id+2000 id, concat(name,'#',id) name FROM aixada_provider";
            $sqlStr .= $all ? "" :" where active=1";
            $sqlStr .= " order by id";
            $strXML .= query_to_XML($sqlStr);
        }
        return '<accounts>'.$strXML.'</accounts>';
    }
    
	public function get_uf_balances_rs($all, $negative) {
		$sql = 
			"select
				a.account_id,
				uf.id as uf, uf.name, 
				a.balance, a.ts as last_update 
			from (select 
					account_id, max(id) as MaxId 
					from aixada_account group by account_id) r		
			join (aixada_account a, aixada_uf uf)
			on a.account_id = r.account_id 
				and a.id = r.MaxId
				and uf.id = a.account_id -1000";
		$sql_where = "";
		$sql_where .= $all ? "" :" and uf.active=1";
		$sql_where .= $negative ? " and a.balance < 0" : "";
		if ($sql_where !== "") {
			$sql .= " where ".substr($sql_where,5);
		}
		$sql .= " order by ";
		if ($negative) {
			$sql .= "a.balance;";
		} else {
			$sql .= "uf.id;";
		}
		return DBWrap::get_instance()->Execute($sql);
	}
	
    public function get_balances_XML($account_types) {
        $strXML = '';
        $result = 0;
        $rs = $this->balances_rs($account_types);
        while ($row = $rs->fetch_assoc()) {
            $result += 
                    $row['account_id'] < 0 ? $row['balance'] : -$row['balance'];
            $strXML .= array_to_XML(array(
                    'account_id' => $row['account_id'],
                    'name' => $row['name'].(
                        $row['account_id'] > 1 ? '#'.$row['account_count'] : ''
                    ),
                    'balance' => $row['balance'],
                    'result' => $result,
                    'last_update' => $row['last_update']
            ));
        }
        $rs->free();
        return '<rowset>'.$strXML.'</rowset>';
    }
    
	protected function get_balances_filter($account_types) {
		$where_array = array();        
        $_key = array_search(1000, $account_types, true);
        if ($_key !== false) {
            array_splice($account_types, $_key, 1);
            array_push($where_array," a.account_id between 1000 and 1999"); 
        }
        $_key = array_search(2000, $account_types, true);
        if ($_key !== false && $this->cfg_use_providers) {
            array_splice($account_types, $_key, 1);
            array_push($where_array," a.account_id between 2000 and 2999"); 
        }
        if (count($account_types) > 0) {
            array_push($where_array,
                " ad.account_type in(".implode(',', $account_types).")"
            ); 
        }
        return ( count($where_array) > 0 ? 
                '( '.implode($where_array, ' or ').' )' : '1=0' );
	}
    protected function balances_rs($account_types) {
        $sql = "
            select account_group_id account_id, account_desciption name,
                count(*) account_count,
                sum(aa.balance) as balance,
                max(aa.ts) as last_update 
            from (
				select 
					case 
						when account_id < 0 then account_id
						when account_id between 1000 and 1999 then 1000
						when account_id between 2000 and 2999 then 2000
						else 0
					end as account_group_id,
					case 
						when a.account_id < 0 then -account_id
						when a.account_id between 1000 and 1999 then 1000
						when a.account_id between 2000 and 2999 then 2000
						else 0
					end as account_group_or,
					case 
						when account_id < 0 then ad.description
						when account_id between 1000 and 1999 then 'UFs'
						when account_id between 2000 and 2999 then 'Providers'
						else '??'
					end as account_desciption,
					account_id,
					max(a.id) as MaxId 
				from aixada_account a
				left join (aixada_account_desc ad)
				on account_id = -ad.id
				where ".$this->get_balances_filter($account_types)."
				group by account_id ) r,
                aixada_account aa
            where 
                r.account_id = aa.account_id
                and aa.id = r.MaxId
            group by account_group_id, account_group_or, account_desciption
            order by account_group_or;";
        return DBWrap::get_instance()->Execute($sql);
    }
    public function income_spending_rs($date, $account_types) {
        $sql = "
            select account_group_id account_id,
					concat(account_desciption,'#',count(*)) name, 
					sum(case when quantity_r > 0 then quantity_r
							 else 0 end) as income,				
					sum(case when quantity_r < 0 then quantity_r
							 else 0 end) as spending,
					sum(quantity_r) as balance   
            from (   
				select 
					quantity quantity_r,
					case 
						when a.account_id < 0 then account_id
						when a.account_id between 1000 and 1999 then 1000
						when a.account_id between 2000 and 2999 then 2000
						else 0
					end as account_group_id,
					case 
						when a.account_id < 0 then -account_id
						when a.account_id between 1000 and 1999 then 1000
						when a.account_id between 2000 and 2999 then 2000
						else 0
					end as account_group_or,
					case 
						when a.account_id < 0 then ad.description
						when a.account_id between 1000 and 1999 then 'UFs'
						when a.account_id between 2000 and 2999 then 'Providers'
						else '??'
					end as account_desciption
				from aixada_account a
				left join (aixada_account_desc ad)
				on a.account_id = -ad.id
				where a.ts between '{$date}' and date_add('{$date}', interval 1 day)
					and ".$this->get_balances_filter($account_types).") r
            group by account_group_id, account_group_or, account_desciption
            order by account_group_or;";
        return DBWrap::get_instance()->Execute($sql);
	}

    // --------------------------------------------
    // ACTIONS
    // --------------------------------------------
    public function add_operation(
                       $account_operation, $accounts, $quantity, $description) {
        global $config_account_operations;
        $_operations = $config_account_operations;
        $_currency_type_id = get_config('currency_type_id',1);

        // chk account_operation
        if (!isset($_operations[$account_operation])) {
            throw new Exception(
              "&account_operation=\"{$account_operation}\" is not configured.");
            exit; 
        }
        $cfg_operation = $_operations[$account_operation];
        
        // chk Amount decimals
        if ($quantity != floor(round($quantity*100, 6))/100) {
            throw new Exception(i18n('mon_war_decimals'));
            exit; 
        }

        // chk accounts and set description if not present
		$op_descr = $description;
        foreach ($cfg_operation as $account_id_name => $o_params) {
            // chk Amount getter that 0
            if ($o_params['sign']) {
                if (!$quantity || $quantity <= 0) {
                    throw new Exception(i18n('mon_war_gt_zero'));
                    exit; 
                }
            }
            if (!isset($accounts[$account_id_name.'_id'])) {
                throw new Exception(i18n('mon_war_accounts_not_set'));
                exit;
            }
            if ($description == '' && isset($o_params['default_desc'])) {
				$op_descr = i18n('mon_desc_'.$o_params['default_desc']);
			}
        }
		if ($op_descr == '') {
			throw new Exception(i18n('mon_war_description'));
			exit;
		}
        
        // All ok!, so do movements
        $success_count = 0;
		$r_replace = array(
			'comment' => $description !== '' ? 
				i18n('comment').': "'.$description.'"' : 
				'');
		foreach ($accounts as $account_id_name => $account_id_value) {
			$r_replace[$account_id_name] = $account_id_value;
		}
		$db = DBWrap::get_instance();
		try {
			$db->start_transaction();
			foreach ($cfg_operation as $account_id_name => $o_params) {
				$_account_id = $accounts[$account_id_name.'_id'];
				if (isset($o_params['auto_desc'])) {
					$item_description = i18n(
						'mon_desc_'.$o_params['auto_desc'],$r_replace);
				} else {
					$item_description = $op_descr;
				}
				$sign = $o_params['sign'];
				if ($sign === 0) {
					if ( $this->correct_balance($db,
							$_account_id, $o_params['method_id'], 
							round($quantity, 2), 
							$item_description,
							$_currency_type_id) ) {
						$success_count++;
					}
				} else {
					if ($_account_id == 1000) { // All active UF is 1000!
						$rs = $db->Execute(
						   "select id from aixada_uf where active = 1 order by id");
						while ($row = $rs->fetch_assoc()) {
							if ( $this->add_movement($db,
										$row['id']+1000, $o_params['method_id'], 
										round($sign * $quantity, 2), 
										$item_description,
										$_currency_type_id) ) {
								$success_count++;
							}
						}
						$db->free_next_results();
					} else {
						if ( $this->add_movement($db,
									$_account_id, $o_params['method_id'], 
									round($sign * $quantity, 2), 
									$item_description,
									$_currency_type_id) ) {
							$success_count++;
						}
					}
				}
			}
			$db->commit();
		} catch (Exception $e) {
			$db->rollback();
			throw new Exception($e->getMessage());
		} 
        return i18n('mon_success', array('count'=>$success_count));
    }
	
	private function add_movement($db, $account_id, $method_id, 
			$quantity, $description, 
			$currency_type_id) {
		$current_balance = 
			$this->chk_account_balance($db, $account_id, $currency_type_id);
		return $db->Insert(array(
			'table' => 'aixada_account', 
			'account_id' => $account_id,
			'quantity' => $quantity,
			'balance' => $current_balance + $quantity,
			'payment_method_id' => $method_id,
			'description' => $description,
			'currency_id' => $currency_type_id,
			'operator_id' => get_session_user_id()	
        ));
    }
    
    private function correct_balance($db, $account_id, $method_id, 
			$new_balance, $description, 
			$currency_type_id) {
        $current_balance = 
			$this->chk_account_balance($db, $account_id, $currency_type_id);
		return $db->Insert(array(
			'table' => 'aixada_account', 
			'account_id' => $account_id,
			'quantity' => $new_balance - $current_balance,
			'balance' => $new_balance,
			'payment_method_id' => $method_id,
			'description' => $description,
			'currency_id' => $currency_type_id,
			'operator_id' => get_session_user_id()	
        ));
    }
    
    private function chk_account_balance($db, $account_id, $currency_type_id) {
        // Create account if not exist
		$row = get_row_query("
			select balance from aixada_account 
				where account_id = {$account_id}
				order by ts desc, id desc
				limit 1");
        if ($row) {
            return $row[0];
        } else {
            if ($db->Insert(array(
                'table' => 'aixada_account',
                'account_id' => $account_id, 
                'quantity' => 0, 
                'payment_method_id' => 11,
                'currency_id' => $currency_type_id, 
                'description' => 'setup',
                'operator_id' => get_session_user_id(),
                'balance' => 0
            ))) {
				return 0;
			} else {
				$bd->rollback();
				throw new Exception("Account setup {$account_id} failed");
				exit;
			}
        }
    }
}
?>
