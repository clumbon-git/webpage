<?php

class C_barcode
{
var $table;
var $code;

function __construct()
{
 $this->table = array(
	 //センターバーより左側のデータキャラクタ - 奇数パリティ
	 0 => array(
		 0 => '0001101',
		 1 => '0011001',
		 2 => '0010011',
		 3 => '0111101',
		 4 => '0100011',
		 5 => '0110001',
		 6 => '0101111',
		 7 => '0111011',
		 8 => '0110111',
		 9 => '0001011',
		),
	 //センターバーより左側のデータキャラクタ - 偶数パリティ
	 1 => array(
		 0 => '0100111',
		 1 => '0110011',
		 2 => '0011011',
		 3 => '0100001',
		 4 => '0011101',
		 5 => '0111001',
		 6 => '0000101',
		 7 => '0010001',
		 8 => '0001001',
		 9 => '0010111',
		),
	 //センターバーより右側のデータキャラクタ - 偶数パリティ
	 2 => array(
		 0 => '1110010',
		 1 => '1100110',
		 2 => '1101100',
		 3 => '1000010',
		 4 => '1011100',
		 5 => '1001110',
		 6 => '1010000',
		 7 => '1000100',
		 8 => '1001000',
		 9 => '1110100',
		),
	);

 $this->startCharacter = '101';
 $this->centerBar = '01010';
 $this->stopCharacter = '101';

 // 0 左奇数パリティ 1 左偶数パリティ
 $this->parity = array(
	 0 => '000000',
	 1 => '001011',
	 2 => '001101',
	 3 => '001110',
	 4 => '010011',
	 5 => '011001',
	 6 => '011100',
	 7 => '010101',
	 8 => '010110',
	 9 => '011010',
	);
}

/**
 *
 *
 * @param s:string
 * @return char(int(1))
 */
function calcCheckDigit($s)
{
 $i = 0;
 $j = 0;
 $result = null;

 switch (strlen($s)) {
	case 7:
	case 8:
	 $i = $s{0} * 3 + $s{1} + $s{2} * 3 + $s{3} + $s{4} * 3 + $s{5} + $s{6} * 3;
	 $result = (10 - $i % 10) % 10;
	 break;

	case 12:
	case 13:
	 $i = $s{0} + $s{1} * 3 + $s{2} + $s{3} * 3 + $s{4} + $s{5} * 3 + $s{6} + $s{7} * 3 + $s{8} + $s{9} * 3 + $s{10} + $s{11} * 3;
	 $result = (10 - $i % 10) % 10;
	 break;
 }

 return $result;
}

/**
 *
 *
 * @param s:string
 * @param b:boolean
 *
 * @return string
 */
function convert($s, $b = false)
{
 $T = '';
 $L = '';
 $R = '';
 $resultL = '';
 $resultR = '';

 switch (strlen($s)) {
	case 7:
	case 8:
	 $s = substr($s, 0, 7) . $this->calcCheckDigit($s);
	 $T = '';
	 $L = substr($s, 0, 4);
	 $R = substr($s, 4, 4);
	 //左半分
	 $resultL = $this->convert8L($L);
	 //右半分
	 $resultR = $this->convert8R($R);
	 break;

	case 12:
	case 13:
	 $s = substr($s, 0, 12) . $this->calcCheckDigit($s);
	 $T = substr($s, 0, 1);
	 $L = substr($s, 1, 6);
	 $R = substr($s, 7, 6);
	 //1文字目を使って、左半分を変換
	 $resultL = $this->convert13L($L, $T);
	 //右半分
	 $resultR = $this->convert13R($R);
	 break;
 }

 $result = null;
 if ($resultL && $resultR) {
	$result = $this->startCharacter . $resultL . $this->centerBar . $resultR . $this->stopCharacter;
	$this->code = $s;
 }

 return $result;
}

function convert13L($L, $T)
{
 $P = $this->parity[$T];

 return $this->table[$P{0}][$L{0}]
		. $this->table[$P{1}][$L{1}]
		. $this->table[$P{2}][$L{2}]
		. $this->table[$P{3}][$L{3}]
		. $this->table[$P{4}][$L{4}]
		. $this->table[$P{5}][$L{5}];
}

function convert13R($R)
{
 return $this->table[2][$R{0}]
		. $this->table[2][$R{1}]
		. $this->table[2][$R{2}]
		. $this->table[2][$R{3}]
		. $this->table[2][$R{4}]
		. $this->table[2][$R{5}];
}
function convert8L($L)
{
 return $this->table[0][$L{0}]
		. $this->table[0][$L{1}]
		. $this->table[0][$L{2}]
		. $this->table[0][$L{3}];
}

function convert8R($R)
{
 return $this->table[2][$R{0}]
		. $this->table[2][$R{1}]
		. $this->table[2][$R{2}]
		. $this->table[2][$R{3}];
}
}