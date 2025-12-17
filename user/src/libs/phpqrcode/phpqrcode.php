<?php
/**
 * PHP QR Code Generator - Lightweight version
 * Based on PHPQRCode library
 * 
 * Simplified for email attachment generation
 */

// QR Code error correction levels
define('QR_ECLEVEL_L', 0);
define('QR_ECLEVEL_M', 1);
define('QR_ECLEVEL_Q', 2);
define('QR_ECLEVEL_H', 3);

// Encoding modes
define('QR_MODE_NM', 0);
define('QR_MODE_AN', 1);
define('QR_MODE_8B', 2);
define('QR_MODE_KJ', 3);

class QRcode {
    
    public $typeNumber;
    public $errorCorrectLevel;
    public $modules;
    public $moduleCount;
    public $dataCache;
    public $dataList = array();
    
    const PAD0 = 0xEC;
    const PAD1 = 0x11;
    
    public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4) {
        $qr = new QRcode();
        $qr->typeNumber = self::getTypeNumber($text, $level);
        $qr->errorCorrectLevel = $level;
        $qr->addData($text);
        $qr->make();
        
        return $qr->createImage($size, $margin, $outfile);
    }
    
    private static function getTypeNumber($text, $level) {
        $length = strlen($text);
        $capacity = array(
            array(17, 14, 11, 7),
            array(32, 26, 20, 14),
            array(53, 42, 32, 24),
            array(78, 62, 46, 34),
            array(106, 84, 60, 44),
            array(134, 106, 74, 58),
            array(154, 122, 86, 64),
            array(192, 152, 108, 84),
            array(230, 180, 130, 98),
            array(271, 213, 151, 119),
        );
        
        for ($i = 0; $i < count($capacity); $i++) {
            if ($length <= $capacity[$i][$level]) {
                return $i + 1;
            }
        }
        return 10;
    }
    
    public function addData($data) {
        $this->dataList[] = new QR8BitByte($data);
        $this->dataCache = null;
    }
    
    public function make() {
        $this->makeImpl(false, $this->getBestMaskPattern());
    }
    
    private function makeImpl($test, $maskPattern) {
        $this->moduleCount = $this->typeNumber * 4 + 17;
        $this->modules = array();
        
        for ($row = 0; $row < $this->moduleCount; $row++) {
            $this->modules[$row] = array();
            for ($col = 0; $col < $this->moduleCount; $col++) {
                $this->modules[$row][$col] = null;
            }
        }
        
        $this->setupPositionProbePattern(0, 0);
        $this->setupPositionProbePattern($this->moduleCount - 7, 0);
        $this->setupPositionProbePattern(0, $this->moduleCount - 7);
        $this->setupPositionAdjustPattern();
        $this->setupTimingPattern();
        $this->setupTypeInfo($test, $maskPattern);
        
        if ($this->typeNumber >= 7) {
            $this->setupTypeNumber($test);
        }
        
        if ($this->dataCache == null) {
            $this->dataCache = self::createData($this->typeNumber, $this->errorCorrectLevel, $this->dataList);
        }
        
        $this->mapData($this->dataCache, $maskPattern);
    }
    
    private function setupPositionProbePattern($row, $col) {
        for ($r = -1; $r <= 7; $r++) {
            if ($row + $r <= -1 || $this->moduleCount <= $row + $r) continue;
            
            for ($c = -1; $c <= 7; $c++) {
                if ($col + $c <= -1 || $this->moduleCount <= $col + $c) continue;
                
                if ((0 <= $r && $r <= 6 && ($c == 0 || $c == 6))
                    || (0 <= $c && $c <= 6 && ($r == 0 || $r == 6))
                    || (2 <= $r && $r <= 4 && 2 <= $c && $c <= 4)) {
                    $this->modules[$row + $r][$col + $c] = true;
                } else {
                    $this->modules[$row + $r][$col + $c] = false;
                }
            }
        }
    }
    
    private function getBestMaskPattern() {
        $minLostPoint = 0;
        $pattern = 0;
        
        for ($i = 0; $i < 8; $i++) {
            $this->makeImpl(true, $i);
            $lostPoint = QRUtil::getLostPoint($this);
            
            if ($i == 0 || $minLostPoint > $lostPoint) {
                $minLostPoint = $lostPoint;
                $pattern = $i;
            }
        }
        
        return $pattern;
    }
    
    private function setupTimingPattern() {
        for ($r = 8; $r < $this->moduleCount - 8; $r++) {
            if ($this->modules[$r][6] !== null) continue;
            $this->modules[$r][6] = ($r % 2 == 0);
        }
        
        for ($c = 8; $c < $this->moduleCount - 8; $c++) {
            if ($this->modules[6][$c] !== null) continue;
            $this->modules[6][$c] = ($c % 2 == 0);
        }
    }
    
    private function setupPositionAdjustPattern() {
        $pos = QRUtil::getPatternPosition($this->typeNumber);
        
        for ($i = 0; $i < count($pos); $i++) {
            for ($j = 0; $j < count($pos); $j++) {
                $row = $pos[$i];
                $col = $pos[$j];
                
                if ($this->modules[$row][$col] !== null) continue;
                
                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        if ($r == -2 || $r == 2 || $c == -2 || $c == 2 || ($r == 0 && $c == 0)) {
                            $this->modules[$row + $r][$col + $c] = true;
                        } else {
                            $this->modules[$row + $r][$col + $c] = false;
                        }
                    }
                }
            }
        }
    }
    
    private function setupTypeNumber($test) {
        $bits = QRUtil::getBCHTypeNumber($this->typeNumber);
        
        for ($i = 0; $i < 18; $i++) {
            $mod = (!$test && (($bits >> $i) & 1) == 1);
            $this->modules[floor($i / 3)][$i % 3 + $this->moduleCount - 8 - 3] = $mod;
        }
        
        for ($i = 0; $i < 18; $i++) {
            $mod = (!$test && (($bits >> $i) & 1) == 1);
            $this->modules[$i % 3 + $this->moduleCount - 8 - 3][floor($i / 3)] = $mod;
        }
    }
    
    private function setupTypeInfo($test, $maskPattern) {
        $data = ($this->errorCorrectLevel << 3) | $maskPattern;
        $bits = QRUtil::getBCHTypeInfo($data);
        
        for ($i = 0; $i < 15; $i++) {
            $mod = (!$test && (($bits >> $i) & 1) == 1);
            
            if ($i < 6) {
                $this->modules[$i][8] = $mod;
            } else if ($i < 8) {
                $this->modules[$i + 1][8] = $mod;
            } else {
                $this->modules[$this->moduleCount - 15 + $i][8] = $mod;
            }
        }
        
        for ($i = 0; $i < 15; $i++) {
            $mod = (!$test && (($bits >> $i) & 1) == 1);
            
            if ($i < 8) {
                $this->modules[8][$this->moduleCount - $i - 1] = $mod;
            } else if ($i < 9) {
                $this->modules[8][15 - $i - 1 + 1] = $mod;
            } else {
                $this->modules[8][15 - $i - 1] = $mod;
            }
        }
        
        $this->modules[$this->moduleCount - 8][8] = (!$test);
    }
    
    private function mapData($data, $maskPattern) {
        $inc = -1;
        $row = $this->moduleCount - 1;
        $bitIndex = 7;
        $byteIndex = 0;
        
        for ($col = $this->moduleCount - 1; $col > 0; $col -= 2) {
            if ($col == 6) $col--;
            
            while (true) {
                for ($c = 0; $c < 2; $c++) {
                    if ($this->modules[$row][$col - $c] === null) {
                        $dark = false;
                        
                        if ($byteIndex < count($data)) {
                            $dark = ((($data[$byteIndex] >> $bitIndex) & 1) == 1);
                        }
                        
                        $mask = QRUtil::getMask($maskPattern, $row, $col - $c);
                        
                        if ($mask) {
                            $dark = !$dark;
                        }
                        
                        $this->modules[$row][$col - $c] = $dark;
                        $bitIndex--;
                        
                        if ($bitIndex == -1) {
                            $byteIndex++;
                            $bitIndex = 7;
                        }
                    }
                }
                
                $row += $inc;
                
                if ($row < 0 || $this->moduleCount <= $row) {
                    $row -= $inc;
                    $inc = -$inc;
                    break;
                }
            }
        }
    }
    
    private static function createData($typeNumber, $errorCorrectLevel, $dataList) {
        $rsBlocks = QRRSBlock::getRSBlocks($typeNumber, $errorCorrectLevel);
        $buffer = new QRBitBuffer();
        
        for ($i = 0; $i < count($dataList); $i++) {
            $data = $dataList[$i];
            $buffer->put($data->getMode(), 4);
            $buffer->put($data->getLength(), QRUtil::getLengthInBits($data->getMode(), $typeNumber));
            $data->write($buffer);
        }
        
        $totalDataCount = 0;
        for ($i = 0; $i < count($rsBlocks); $i++) {
            $totalDataCount += $rsBlocks[$i]->getDataCount();
        }
        
        if ($buffer->getLengthInBits() > $totalDataCount * 8) {
            throw new Exception("Code length overflow");
        }
        
        if ($buffer->getLengthInBits() + 4 <= $totalDataCount * 8) {
            $buffer->put(0, 4);
        }
        
        while ($buffer->getLengthInBits() % 8 != 0) {
            $buffer->putBit(false);
        }
        
        while (true) {
            if ($buffer->getLengthInBits() >= $totalDataCount * 8) break;
            $buffer->put(self::PAD0, 8);
            
            if ($buffer->getLengthInBits() >= $totalDataCount * 8) break;
            $buffer->put(self::PAD1, 8);
        }
        
        return self::createBytes($buffer, $rsBlocks);
    }
    
    private static function createBytes($buffer, $rsBlocks) {
        $offset = 0;
        $maxDcCount = 0;
        $maxEcCount = 0;
        
        $dcdata = array();
        $ecdata = array();
        
        for ($r = 0; $r < count($rsBlocks); $r++) {
            $dcCount = $rsBlocks[$r]->getDataCount();
            $ecCount = $rsBlocks[$r]->getTotalCount() - $dcCount;
            
            $maxDcCount = max($maxDcCount, $dcCount);
            $maxEcCount = max($maxEcCount, $ecCount);
            
            $dcdata[$r] = array();
            for ($i = 0; $i < $dcCount; $i++) {
                $dcdata[$r][$i] = 0xff & $buffer->getBuffer()[$i + $offset];
            }
            $offset += $dcCount;
            
            $rsPoly = QRUtil::getErrorCorrectPolynomial($ecCount);
            $rawPoly = new QRPolynomial($dcdata[$r], $rsPoly->getLength() - 1);
            $modPoly = $rawPoly->mod($rsPoly);
            
            $ecdata[$r] = array();
            for ($i = 0; $i < $rsPoly->getLength() - 1; $i++) {
                $modIndex = $i + $modPoly->getLength() - ($rsPoly->getLength() - 1);
                $ecdata[$r][$i] = ($modIndex >= 0) ? $modPoly->get($modIndex) : 0;
            }
        }
        
        $totalCodeCount = 0;
        for ($i = 0; $i < count($rsBlocks); $i++) {
            $totalCodeCount += $rsBlocks[$i]->getTotalCount();
        }
        
        $data = array();
        $index = 0;
        
        for ($i = 0; $i < $maxDcCount; $i++) {
            for ($r = 0; $r < count($rsBlocks); $r++) {
                if ($i < count($dcdata[$r])) {
                    $data[$index++] = $dcdata[$r][$i];
                }
            }
        }
        
        for ($i = 0; $i < $maxEcCount; $i++) {
            for ($r = 0; $r < count($rsBlocks); $r++) {
                if ($i < count($ecdata[$r])) {
                    $data[$index++] = $ecdata[$r][$i];
                }
            }
        }
        
        return $data;
    }
    
    private function createImage($size, $margin, $outfile) {
        $imageSize = $this->moduleCount * $size + $margin * 2;
        $image = imagecreatetruecolor($imageSize, $imageSize);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        for ($row = 0; $row < $this->moduleCount; $row++) {
            for ($col = 0; $col < $this->moduleCount; $col++) {
                if ($this->modules[$row][$col]) {
                    $x = $col * $size + $margin;
                    $y = $row * $size + $margin;
                    imagefilledrectangle($image, $x, $y, $x + $size - 1, $y + $size - 1, $black);
                }
            }
        }
        
        if ($outfile === false) {
            header('Content-Type: image/png');
            imagepng($image);
        } else {
            imagepng($image, $outfile);
        }
        
        imagedestroy($image);
        return true;
    }
}

class QR8BitByte {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function getMode() {
        return QR_MODE_8B;
    }
    
    public function getLength() {
        return strlen($this->data);
    }
    
    public function write($buffer) {
        for ($i = 0; $i < strlen($this->data); $i++) {
            $buffer->put(ord($this->data[$i]), 8);
        }
    }
}

class QRUtil {
    private static $PATTERN_POSITION_TABLE = array(
        array(),
        array(6, 18),
        array(6, 22),
        array(6, 26),
        array(6, 30),
        array(6, 34),
        array(6, 22, 38),
        array(6, 24, 42),
        array(6, 26, 46),
        array(6, 28, 50),
        array(6, 30, 54),
        array(6, 32, 58),
        array(6, 34, 62),
        array(6, 26, 46, 66),
        array(6, 26, 48, 70),
        array(6, 26, 50, 74),
        array(6, 30, 54, 78),
        array(6, 30, 56, 82),
        array(6, 30, 58, 86),
        array(6, 34, 62, 90),
        array(6, 28, 50, 72, 94),
        array(6, 26, 50, 74, 98),
        array(6, 30, 54, 78, 102),
        array(6, 28, 54, 80, 106),
        array(6, 32, 58, 84, 110),
        array(6, 30, 58, 86, 114),
        array(6, 34, 62, 90, 118),
        array(6, 26, 50, 74, 98, 122),
        array(6, 30, 54, 78, 102, 126),
        array(6, 26, 52, 78, 104, 130),
        array(6, 30, 56, 82, 108, 134),
        array(6, 34, 60, 86, 112, 138),
        array(6, 30, 58, 86, 114, 142),
        array(6, 34, 62, 90, 118, 146),
        array(6, 30, 54, 78, 102, 126, 150),
        array(6, 24, 50, 76, 102, 128, 154),
        array(6, 28, 54, 80, 106, 132, 158),
        array(6, 32, 58, 84, 110, 136, 162),
        array(6, 26, 54, 82, 110, 138, 166),
        array(6, 30, 58, 86, 114, 142, 170),
    );
    
    private static $G15 = (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0);
    private static $G18 = (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0);
    private static $G15_MASK = (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1);
    
    public static function getBCHTypeInfo($data) {
        $d = $data << 10;
        while (self::getBCHDigit($d) - self::getBCHDigit(self::$G15) >= 0) {
            $d ^= (self::$G15 << (self::getBCHDigit($d) - self::getBCHDigit(self::$G15)));
        }
        return (($data << 10) | $d) ^ self::$G15_MASK;
    }
    
    public static function getBCHTypeNumber($data) {
        $d = $data << 12;
        while (self::getBCHDigit($d) - self::getBCHDigit(self::$G18) >= 0) {
            $d ^= (self::$G18 << (self::getBCHDigit($d) - self::getBCHDigit(self::$G18)));
        }
        return ($data << 12) | $d;
    }
    
    private static function getBCHDigit($data) {
        $digit = 0;
        while ($data != 0) {
            $digit++;
            $data >>= 1;
        }
        return $digit;
    }
    
    public static function getPatternPosition($typeNumber) {
        return self::$PATTERN_POSITION_TABLE[$typeNumber - 1];
    }
    
    public static function getMask($maskPattern, $i, $j) {
        switch ($maskPattern) {
            case 0: return ($i + $j) % 2 == 0;
            case 1: return $i % 2 == 0;
            case 2: return $j % 3 == 0;
            case 3: return ($i + $j) % 3 == 0;
            case 4: return (floor($i / 2) + floor($j / 3)) % 2 == 0;
            case 5: return ($i * $j) % 2 + ($i * $j) % 3 == 0;
            case 6: return (($i * $j) % 2 + ($i * $j) % 3) % 2 == 0;
            case 7: return (($i * $j) % 3 + ($i + $j) % 2) % 2 == 0;
            default: throw new Exception("Invalid mask pattern");
        }
    }
    
    public static function getErrorCorrectPolynomial($errorCorrectLength) {
        $a = new QRPolynomial(array(1), 0);
        for ($i = 0; $i < $errorCorrectLength; $i++) {
            $a = $a->multiply(new QRPolynomial(array(1, QRMath::gexp($i)), 0));
        }
        return $a;
    }
    
    public static function getLengthInBits($mode, $type) {
        if ($type >= 1 && $type < 10) {
            switch ($mode) {
                case QR_MODE_NM: return 10;
                case QR_MODE_AN: return 9;
                case QR_MODE_8B: return 8;
                case QR_MODE_KJ: return 8;
            }
        } else if ($type < 27) {
            switch ($mode) {
                case QR_MODE_NM: return 12;
                case QR_MODE_AN: return 11;
                case QR_MODE_8B: return 16;
                case QR_MODE_KJ: return 10;
            }
        } else if ($type < 41) {
            switch ($mode) {
                case QR_MODE_NM: return 14;
                case QR_MODE_AN: return 13;
                case QR_MODE_8B: return 16;
                case QR_MODE_KJ: return 12;
            }
        }
        throw new Exception("Invalid type: $type");
    }
    
    public static function getLostPoint($qrCode) {
        $moduleCount = $qrCode->moduleCount;
        $lostPoint = 0;
        
        // Level 1
        for ($row = 0; $row < $moduleCount; $row++) {
            for ($col = 0; $col < $moduleCount; $col++) {
                $sameCount = 0;
                $dark = $qrCode->modules[$row][$col];
                
                for ($r = -1; $r <= 1; $r++) {
                    if ($row + $r < 0 || $moduleCount <= $row + $r) continue;
                    
                    for ($c = -1; $c <= 1; $c++) {
                        if ($col + $c < 0 || $moduleCount <= $col + $c) continue;
                        if ($r == 0 && $c == 0) continue;
                        
                        if ($dark == $qrCode->modules[$row + $r][$col + $c]) {
                            $sameCount++;
                        }
                    }
                }
                
                if ($sameCount > 5) {
                    $lostPoint += (3 + $sameCount - 5);
                }
            }
        }
        
        return $lostPoint;
    }
}

class QRMath {
    private static $EXP_TABLE = null;
    private static $LOG_TABLE = null;
    
    public static function init() {
        if (self::$EXP_TABLE === null) {
            self::$EXP_TABLE = array();
            self::$LOG_TABLE = array();
            
            for ($i = 0; $i < 8; $i++) {
                self::$EXP_TABLE[$i] = 1 << $i;
            }
            
            for ($i = 8; $i < 256; $i++) {
                self::$EXP_TABLE[$i] = self::$EXP_TABLE[$i - 4]
                    ^ self::$EXP_TABLE[$i - 5]
                    ^ self::$EXP_TABLE[$i - 6]
                    ^ self::$EXP_TABLE[$i - 8];
            }
            
            for ($i = 0; $i < 255; $i++) {
                self::$LOG_TABLE[self::$EXP_TABLE[$i]] = $i;
            }
        }
    }
    
    public static function glog($n) {
        self::init();
        if ($n < 1) throw new Exception("glog($n)");
        return self::$LOG_TABLE[$n];
    }
    
    public static function gexp($n) {
        self::init();
        while ($n < 0) $n += 255;
        while ($n >= 256) $n -= 255;
        return self::$EXP_TABLE[$n];
    }
}

class QRPolynomial {
    private $num;
    
    public function __construct($num, $shift) {
        $offset = 0;
        while ($offset < count($num) && $num[$offset] == 0) {
            $offset++;
        }
        
        $this->num = array();
        for ($i = 0; $i < count($num) - $offset + $shift; $i++) {
            $this->num[$i] = 0;
        }
        
        for ($i = 0; $i < count($num) - $offset; $i++) {
            $this->num[$i] = $num[$i + $offset];
        }
    }
    
    public function get($index) {
        return $this->num[$index];
    }
    
    public function getLength() {
        return count($this->num);
    }
    
    public function multiply($e) {
        $num = array();
        for ($i = 0; $i < $this->getLength() + $e->getLength() - 1; $i++) {
            $num[$i] = 0;
        }
        
        for ($i = 0; $i < $this->getLength(); $i++) {
            for ($j = 0; $j < $e->getLength(); $j++) {
                $num[$i + $j] ^= QRMath::gexp(QRMath::glog($this->get($i)) + QRMath::glog($e->get($j)));
            }
        }
        
        return new QRPolynomial($num, 0);
    }
    
    public function mod($e) {
        if ($this->getLength() - $e->getLength() < 0) {
            return $this;
        }
        
        $ratio = QRMath::glog($this->get(0)) - QRMath::glog($e->get(0));
        
        $num = array();
        for ($i = 0; $i < $this->getLength(); $i++) {
            $num[$i] = $this->get($i);
        }
        
        for ($i = 0; $i < $e->getLength(); $i++) {
            $num[$i] ^= QRMath::gexp(QRMath::glog($e->get($i)) + $ratio);
        }
        
        return (new QRPolynomial($num, 0))->mod($e);
    }
}

class QRRSBlock {
    private $totalCount;
    private $dataCount;
    
    private static $RS_BLOCK_TABLE = array(
        array(1, 26, 19), array(1, 26, 16), array(1, 26, 13), array(1, 26, 9),
        array(1, 44, 34), array(1, 44, 28), array(1, 44, 22), array(1, 44, 16),
        array(1, 70, 55), array(1, 70, 44), array(2, 35, 17), array(2, 35, 13),
        array(1, 100, 80), array(2, 50, 32), array(2, 50, 24), array(4, 25, 9),
        array(1, 134, 108), array(2, 67, 43), array(2, 33, 15, 2, 34, 16), array(2, 33, 11, 2, 34, 12),
        array(2, 86, 68), array(4, 43, 27), array(4, 43, 19), array(4, 43, 15),
        array(2, 98, 78), array(4, 49, 31), array(2, 32, 14, 4, 33, 15), array(4, 39, 13, 1, 40, 14),
        array(2, 121, 97), array(2, 60, 38, 2, 61, 39), array(4, 40, 18, 2, 41, 19), array(4, 40, 14, 2, 41, 15),
        array(2, 146, 116), array(3, 58, 36, 2, 59, 37), array(4, 36, 16, 4, 37, 17), array(4, 36, 12, 4, 37, 13),
        array(2, 86, 68, 2, 87, 69), array(4, 69, 43, 1, 70, 44), array(6, 43, 19, 2, 44, 20), array(6, 43, 15, 2, 44, 16),
    );
    
    public function __construct($totalCount, $dataCount) {
        $this->totalCount = $totalCount;
        $this->dataCount = $dataCount;
    }
    
    public function getTotalCount() {
        return $this->totalCount;
    }
    
    public function getDataCount() {
        return $this->dataCount;
    }
    
    public static function getRSBlocks($typeNumber, $errorCorrectLevel) {
        $rsBlock = self::getRsBlockTable($typeNumber, $errorCorrectLevel);
        if ($rsBlock === null) {
            throw new Exception("Bad RS block @ typeNumber: $typeNumber / errorCorrectLevel: $errorCorrectLevel");
        }
        
        $length = count($rsBlock) / 3;
        $list = array();
        
        for ($i = 0; $i < $length; $i++) {
            $count = $rsBlock[$i * 3 + 0];
            $totalCount = $rsBlock[$i * 3 + 1];
            $dataCount = $rsBlock[$i * 3 + 2];
            
            for ($j = 0; $j < $count; $j++) {
                $list[] = new QRRSBlock($totalCount, $dataCount);
            }
        }
        
        return $list;
    }
    
    private static function getRsBlockTable($typeNumber, $errorCorrectLevel) {
        switch ($errorCorrectLevel) {
            case QR_ECLEVEL_L: return self::$RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 0];
            case QR_ECLEVEL_M: return self::$RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 1];
            case QR_ECLEVEL_Q: return self::$RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 2];
            case QR_ECLEVEL_H: return self::$RS_BLOCK_TABLE[($typeNumber - 1) * 4 + 3];
            default: return null;
        }
    }
}

class QRBitBuffer {
    private $buffer = array();
    private $length = 0;
    
    public function getBuffer() {
        return $this->buffer;
    }
    
    public function get($index) {
        $bufIndex = floor($index / 8);
        return (($this->buffer[$bufIndex] >> (7 - $index % 8)) & 1) == 1;
    }
    
    public function put($num, $length) {
        for ($i = 0; $i < $length; $i++) {
            $this->putBit((($num >> ($length - $i - 1)) & 1) == 1);
        }
    }
    
    public function getLengthInBits() {
        return $this->length;
    }
    
    public function putBit($bit) {
        $bufIndex = floor($this->length / 8);
        if (count($this->buffer) <= $bufIndex) {
            $this->buffer[] = 0;
        }
        
        if ($bit) {
            $this->buffer[$bufIndex] |= (0x80 >> ($this->length % 8));
        }
        
        $this->length++;
    }
}
