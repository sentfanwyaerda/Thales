<?php 
class Thales {
	var $menu = array(); 
	
	function load_menu($src=FALSE){
		if($src == FALSE){ $src = dirname(__FILE__).'/menu.json'; }
		$str = file_get_contents($src);
		$menu = json_decode($str, TRUE);
		if(isset($this)){ $this->menu = $menu; }
		return $menu;
	}
	function generate_menu($json=FALSE, $depth=0){
		/*fix*/ if($json === FALSE){ $json = (isset($this->menu) ? $this->menu : array() ); }
		//*debug*/ print_r($json);
		
		$str = NULL;
		$str .= "\n".str_repeat("\t", ($depth *2)).'<ul class="'.($depth == 0 ? 'sidebar-menu' : 'treeview-menu').'">'."\n";
		foreach($json as $i=>$item){
			$has_children = (isset($item['items']) && is_array($item['items']) && count($item['items']) > 0 ? TRUE : FALSE);
			
			$liclass = array();
			if($has_children == TRUE){ $liclass[] = "treeview"; }
			if(isset($item['active'])){ $liclass[] = ($item['active'] == TRUE || in_array(strtolower($item['active']), array('yes','true','open')) ? 'active' : 'passive'); }
			if(isset($item['class'])){ $liclass[] = $item['class']; }
			$str .= str_repeat("\t", ($depth *2)+1).'<li'.(count($liclass) == 0 ? NULL : ' class="'.implode(' ', $liclass).'"').'>';
			if(isset($item['url'])){ $str .= '<a href="'.$item['url'].'">'; }
			elseif($has_children == TRUE){ $str .= '<a href="#">'; }
			if(isset($item['icon'])){ $str .= '<i class="fa '.$item['icon'].'"></i>'; }
			else{ $str .= '<i class="fa fa-circle-o"></i>'; }
			$str .= '<span>'.(isset($item['text']) ? $item['text'] : '&nbsp;').'</span>';
			if(isset($item['notice'])){ $str .= ' <'.(isset($item['notice']['tag']) ? strtolower($item['notice']['tag']) : 'span').' class="label pull-right '.$item['notice']['class'].'">'.$item['notice']['text'].'</'.(isset($item['notice']['tag']) ? strtolower($item['notice']['tag']) : 'span').'>'; }
			elseif($has_children == TRUE){ $str .= ' <i class="fa fa-angle-left pull-right"></i>'; }
			if(isset($item['url']) || $has_children == TRUE){ $str .= '</a>'; }
			if($has_children == TRUE){ $str .= Thales::generate_menu($item['items'], $depth+1); }
			$str .= '</li>'."\n";
		}
		$str .= str_repeat("\t", ($depth *2)).'</ul>';
		return $str;
	}
	function find_item($path=NULL){
		/*notify*/ print '<!-- find_item ('.$path.') -->';
		/*fix*/ if($path == '/'){ return TRUE; }
		/*fix*/ $path = Thales::gen_path($path);
		$result = array();
		$data = Thales::flatten($this->menu);
		foreach($data as $id=>$obj){
			if(isset($obj['%path']) && $obj['%path'] == $path){
				//*debug*/ print_r($id);
				$result = Thales::blowup($data, $id);
				$result['%id'] = $id;
			}
		}
		if($result === array()){ return FALSE; }
		return $result;
	}
	function add_item($name=NULL,$options=NULL,$create=FALSE){
		if(isset($this)){
			$add = array();
			$add['text'] = basename($name);
			if(is_array($options)){ $add = array_merge($add, $options); }
			elseif(is_string($options)){
				$json = json_decode($options, TRUE);
				if(is_array($json) && count($json) > 0){
					$add = array_merge($add, $json);
				}
				else{ $add['url'] = $options; }
			}
			
			$found = Thales::find_item(dirname($name));
			/*notify*/ print '<!-- is ('.print_r($found, TRUE).') -->';
			if($create !== FALSE && !$found && dirname($name) != '/'){
				$this->add_item(dirname($name),NULL,TRUE);
				$found = Thales::find_item(dirname($name));
				/*notify*/ print '<!-- created ('.print_r($found, TRUE).') -->';
			}
			
			if(isset($found['%id'])){ // && dirname($name) != '/'
				$data = Thales::flatten($this->menu);
				$data[$found['%id']]['items'][] = $add;
				$this->menu = Thales::blowup($data);
			} else {
				$this->menu[] = $add;
			}
			return $add;
		} else { return FALSE; }		
	}
	function gen_path($item, $prefix=FALSE){
		$text = (is_array($item) ? $item['text'] : $item);
		return strtolower(($prefix == FALSE ? (substr($text, 0, 1) == '/' ? NULL : '/') : preg_replace("#[^a-z0-9/]#i", "-", $prefix).'/').preg_replace("#[^a-z0-9/]#i", "-", $text));
	}
	function flatten($json=array(), $prefix=FALSE, $parent=FALSE, $set=array()){
		/*fix*/ if(!(isset($set) && is_array($set))){ $set = array(); }
		/*fix*/ if(!isset($set['%base'])){ $set['%base'] = array(); }
		
		if(is_array($json)){foreach($json as $i=>$item){
			$path = Thales::gen_path($item, $prefix);
			$id = md5(json_encode($item));
			/*fix*/ if(!isset($set[$id])){ $set[$id] = array(); }
			if(isset($item['items']) && is_array($item['items'])){
				$set = Thales::flatten($item['items'], $path, $id, $set);
				unset($item['items']);
			}
			$set[$id] = array_merge((isset($set[$id]) && is_array($set[$id]) ? $set[$id] : array()), $item);
			$set[$id]['%path'] = Thales::gen_path($path);
			$set[$id]['%parent'] = ($parent == FALSE ? '%base' : $parent);
			$set[($parent == FALSE ? '%base' : $parent)]['%items'][] = $id;
		}}
		return $set;
	}
	function blowup($set=array(), $focus=NULL){
		/*fix*/ if($focus === NULL){ $focus = '%base'; }
		foreach($set as $pid=>$item){
			if($pid == $focus){
				unset($set[$pid]['%path']);
				unset($set[$pid]['%parent']);
				if(isset($item['%items']) && is_array($item['%items'])){
					foreach($item['%items'] as $i=>$id){
						$set[$pid]['items'][] = Thales::blowup($set, $id);
					}
					unset($set[$pid]['%items']);
				}
			}
		}
		return (isset($set[$focus]) ? ($focus == '%base' && count($set[$focus]) == 1 ? $set['%base']['items'] : $set[$focus]) : array());
	}
}
?>