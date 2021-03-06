//<?php
$__workDir__ = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
$__baseXMLFile__ = 'base.xml';
$__XMLdata__ = '';
$__enablesave__ = true;
if (isset($_GET['open']) && file_exists($__workDir__ . $__baseXMLFile__)) {
	header('Content-type: text/xml');
	header('Date-update: ' . fileatime($__workDir__ . $__baseXMLFile__));
	echo file_get_contents($__workDir__ . $__baseXMLFile__);
	$__enablesave__ = false;
	die;
}

function incID($id)
{
	static $__INCID__ = [];
	$id = (int)$id;
	if (in_array($id,$__INCID__)) return incID(++$id);
	$__INCID__[] = $id;
	return $id;
}

function __exittoxmlsave__()
{
	global $__workDir__, $__baseXMLFile__, $__XMLdata__, $__enablesave__;
	if ($__enablesave__) file_put_contents($__workDir__ . $__baseXMLFile__, $__XMLdata__);
}

register_shutdown_function('__exittoxmlsave__');

function postData($url, $postdata)
	{
		/*$postdata = http_build_query($data);*/

		$opts = array('http' =>
				array(
						'method'  => 'POST',
						//'header'  => 'Content-Type: application/json'."\r\n".'X-Requested-With: XMLHttpRequest',
						'content' => $postdata
				)
		);

		$context  = stream_context_create($opts);

		return @file_get_contents($url, false, $context);
	}

function toXML($object)
{
	global $__X__;
	$__X__ = xmlwriter_open_memory();
	xmlwriter_start_document($__X__,'1.0','UTF-8');
	function isAssoc($array)
	{
		$keys = array_keys($array);
		return array_keys($keys) !== $keys;
	}
	function convert($o, $bTag = null)
	{
		global $__X__;
		switch (gettype($o))
		{
			case 'array':

				foreach ($o as $attr => $data)
				{	
					if (!$data) continue;
					switch (gettype($attr))
					{
						case 'string':
							if ($attr[0] == '#')
							{
								switch ($attr)
								{
									case '#data':
										if ($data) convert($data, $bTag);
									break;
									case '#attr':
										if ($data) 
										{
											if (gettype($data)=='array')
											{
												foreach ($data as $name=>$value)
												{
													if ($value) xmlwriter_write_attribute ($__X__, $name, $value);
												} 
											}
										}
									break;
								}
							}
							else
							{
								switch (gettype($data))
								{
									case 'string':
									case 'integer':
									case 'double':
										if ($data)
										{
											xmlwriter_start_element($__X__, $attr);
											xmlwriter_text($__X__, $data);
											xmlwriter_end_element($__X__);
										}
									break;
									case 'array':

										if (isAssoc($data))
										{
											@xmlwriter_start_element($__X__, $attr);
											convert($data, $attr);
											xmlwriter_end_element($__X__);
										}
										else convert($data, $attr);

									break;
								}
							}
						break;
						case 'integer':
							if ($bTag != null && $data)
							{
								xmlwriter_start_element($__X__, $bTag);
								convert($data, $bTag);
								xmlwriter_end_element($__X__);
							}
						break;
					}
				}
			break;
			case 'string':
			case 'integer':
			case 'double':
				if ($o) xmlwriter_text($__X__, $o);
			break;
		}
	}
	xmlwriter_end_dtd($__X__);
	convert($object);
	return xmlwriter_output_memory($__X__, true);
}

function getNumberMonth($month)
{
	if (gettype($month) != 'string') return $month;
	$month = substr($month,0,6);
	switch($month)
	{
		case "Янв":case "янв":
			return 1;
		break;
		case "Фев":case "фев":
			return 2;
		break;
		case "Мар":case "мар":
			return 3;
		break;
		case "Апр":case "апр":
			return 4;
		break;
		case "Май":case "май":
		case "Мая":case "мая":
			return 5;
		break;
		case "Июн":case "июн":
			return 6;
		break;
		case "Июл":case "июл":
			return 7;
		break;
		case "Авг":case "авг":
			return 8;
		break;
		case "Сен":case "сен":
			return 9;
		break;
		case "Окт":case "окт":
			return 10;
		break;
		case "Ноя":case "ноя":
			return 11;
		break;
		case "Дек":case "дек":
			return 12;
		break;
	}
	return 0;
}


function createHouse($rooms=[], $images = [], $atags = ['rooms','room'])
{
	global $__XMLdata__;
	$zhks = [];
	$dinamics = [];
	$korps = [];
	$baseid = [];
	
	$tags = $atags[0];
	$tag = $atags[1];

	foreach ($rooms as $key => $room)
	{ 
		if (array_key_exists('title', $room)) 
		{
			$title = $room['title'];
			unset($room['title']);
		}
		else $title = 'пустой title';
		if (array_key_exists('korp', $room)) 
		{
			$korp = $room['korp'];
			unset($room['korp']);
		}
		else $korp = '1';

		$title = trim($title);
		$korp = trim($korp);

		if (!array_key_exists($title, $zhks)) $zhks[$title] = [];
		if (!array_key_exists($korp, $zhks[$title])) $zhks[$title][$korp] = [];

		if (array_key_exists('floor', $room)) $room['floor'] = $floor = (int)$room['floor'];
		else $floor = 0;
		
		if (array_key_exists('sale_price', $room)) $room['sale_price'] = (int)preg_replace('#[^\d]#ui', '', $room['sale_price']);
		else $floor = 0;
		
		if (array_key_exists('number', $room)) $number = $room['number'];
		else $number = 0;

		if (array_key_exists('num', $room) && $room['num'] != 's') $room['num']= $num = (int)$room['num'];
		else $num = 0;
		
		if (array_key_exists('price', $room)){
			$room['price'] = (int)filter_var($room['price'], FILTER_VALIDATE_INT);
		}

		if (array_key_exists('square', $room)) $square = $room['square'] = (float)$room['square'];
		elseif (array_key_exists('area', $room)) $square = $room['area'] = (float)$room['area'];
		else $square = 0;

		if (!array_key_exists('id', $room))
		{
			$id = (int)preg_replace('#[^\d]#ui', '', ''.$korp.$number.$num.$floor.$square);
			while(1)
			{
				if (in_array($id,$baseid)) 
				{
					$id++;
					continue;
				}
				$baseid[] = $id;
				break;
			}
			$room = ['#attr' => ['offer_id'=>$id], 'id' => $id] + $room;
		}

		$zhks[$title][$korp][] = $room;
		ksort($zhks[$title]);
	}

	foreach ($images as $key => $image)
	{ 
		if (array_key_exists('title', $image)) 
		{
			$title = $image['title'];
			unset($image['title']);
		}
		else $title = 'пустой title';
		
		if (array_key_exists('#attr', $image) && array_key_exists('month', $image['#attr'])) $image['#attr']['month'] = getNumberMonth($image['#attr']['month']);

		if (!array_key_exists($title, $dinamics)) $dinamics[$title] = [];
		$dinamics[$title][] = $image;
	}
	
	if (count($images))
	{
		usort($dinamics[$title], function($a, $b){
			$month1 = $a['#attr']['year']*100+$a['#attr']['month']; 
			$month2 = $b['#attr']['year']*100+$b['#attr']['month'];
			return ($month1<$month2);
		});
	}
	
	$offers = [];
	$i = 0;

	foreach ($zhks as $name => $korps)
	{
		$rooms = [];
		foreach ($korps as $nameKorp => $korpData)
		{
			$rooms[] = [
				'num' => $nameKorp
				,$tags => [$tag => $korpData]
			];
		}
		$offers[$i] = [
			'build-name' => $name
			,'korps' => ['korp'=>$rooms]
		];
		foreach ($dinamics as $nameZHKdyn => $data) {
			similar_text($nameZHKdyn,$name,$prc);
			if($prc >= 80)
			{
				$offers[$i]['images'] = ['image'=>$dinamics[$nameZHKdyn]];
				unset($dinamics[$nameZHKdyn]);
			}
		}
		$i++;
	}

	foreach ($dinamics as $name => $image)
	{
		if (array_key_exists('month', $image)) $image['month'] = getNumberMonth($image['month']);
		$offers[$i] = [
			'build-name' => $name
			,'images' => ['image'=>$dinamics[$name]]
		];
		$i++;
	}

	$data = [
		'offers' => [
			'offer' => $offers
		]
	];
	$__XMLdata__ = toXML($data);
	return $data;
}
