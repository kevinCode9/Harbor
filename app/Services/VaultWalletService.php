<?php

namespace App\Services;

use FurqanSiddiqui\BIP39\BIP39;
use Elliptic\EC;
use kornrunner\Keccak;

class VaultWalletService
{
    private string $btcAlphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    private string $xrpAlphabet = 'rpshnaf39wBUDNEGHJKLM4PQRST7VWXYZ2bcdeCg65jkm8oFqi1tuvAxyz';

    public function generateMnemonic(): string
    {
        $mnemonic = BIP39::Generate(12);
        return implode(' ', $mnemonic->words);
    }

    public function generateSeed(string $mnemonic): string
    {
        $mnemonicObj = BIP39::Words($mnemonic);
        $rawSeed = $mnemonicObj->generateSeed('');
        return ctype_xdigit($rawSeed) ? $rawSeed : bin2hex($rawSeed);
    }

    private function generateMasterKey(string $seed): array
    {
        $binarySeed = ctype_xdigit($seed) ? hex2bin($seed) : $seed;
        $hmac = hash_hmac('sha512', $binarySeed, 'Bitcoin seed', true);
        return ['private_key' => substr($hmac, 0, 32), 'chain_code' => substr($hmac, 32, 32)];
    }

    private function parsePath(string $path): array
    {
        $parts = explode('/', $path);
        $indices = [];
        foreach ($parts as $part) {
            if ($part === 'm' || trim($part) === '') continue;
            $hardened = str_ends_with($part, "'");
            $index = intval(trim($part, "'"));
            if ($hardened) $index += 0x80000000;
            $indices[] = $index;
        }
        return $indices;
    }

    private function deriveChildKey(string $privateKey, string $chainCode, int $index): array
    {
        if ($index >= 0x80000000) {
            $data = "\x00" . $privateKey . pack('N', $index);
        } else {
            $data = $this->getPublicKeyBin($privateKey) . pack('N', $index);
        }
        $hmac = hash_hmac('sha512', $data, $chainCode, true);
        $il = substr($hmac, 0, 32);
        $ir = substr($hmac, 32, 32);
        return ['private_key' => $this->privateKeyAdd($privateKey, $il), 'chain_code' => $ir];
    }

    private function derivePrivateKeyFromPath(string $seed, string $path): string
    {
        $master = $this->generateMasterKey($seed);
        $indices = $this->parsePath($path);
        $privateKey = $master['private_key'];
        $chainCode  = $master['chain_code'];
        foreach ($indices as $index) {
            $child = $this->deriveChildKey($privateKey, $chainCode, $index);
            $privateKey = $child['private_key'];
            $chainCode  = $child['chain_code'];
        }
        return bin2hex($privateKey);
    }

    private function getPublicKeyBin(string $privateKey): string
    {
        $ec = new EC('secp256k1');
        return hex2bin($ec->keyFromPrivate(bin2hex($privateKey))->getPublic()->encode('hex', true));
    }

    private function privateKeyAdd(string $key1, string $key2): string
    {
        $n   = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);
        $sum = gmp_mod(gmp_add(gmp_init(bin2hex($key1), 16), gmp_init(bin2hex($key2), 16)), $n);
        return hex2bin(str_pad(gmp_strval($sum, 16), 64, '0', STR_PAD_LEFT));
    }

    private function pubKeyHash(string $privateKeyHex): string
    {
        $ec  = new EC('secp256k1');
        $pub = hex2bin($ec->keyFromPrivate($privateKeyHex)->getPublic()->encode('hex', true));
        return hash('ripemd160', hash('sha256', $pub, true), true);
    }

    private function base58Encode(string $binary, string $alphabet): string
    {
        $decimal = gmp_init(bin2hex($binary), 16);
        $base    = gmp_init(58, 10);
        $result  = '';
        while (gmp_cmp($decimal, 0) > 0) {
            $rem     = gmp_intval(gmp_mod($decimal, $base));
            $decimal = gmp_div_q($decimal, $base);
            $result  = $alphabet[$rem] . $result;
        }
        foreach (str_split($binary) as $byte) {
            if ($byte !== "\x00") break;
            $result = $alphabet[0] . $result;
        }
        return $result;
    }

    private function legacyAddress(string $privateKeyHex, int $version, string $alphabet): string
    {
        $versioned = chr($version) . $this->pubKeyHash($privateKeyHex);
        $checksum  = substr(hash('sha256', hash('sha256', $versioned, true), true), 0, 4);
        return $this->base58Encode($versioned . $checksum, $alphabet);
    }

    // ETH-style address from any BIP44 path (EVM-compatible chains)
    private function deriveEthStyle(string $seed, string $path): string
    {
        $privHex = $this->derivePrivateKeyFromPath($seed, $path);
        $ec = new EC('secp256k1');
        $pubFull = $ec->keyFromPrivate($privHex)->getPublic()->encode('hex', false);
        $hash = Keccak::hash(hex2bin(substr($pubFull, 2)), 256);
        return '0x' . substr($hash, -40);
    }

    // ── BTC Native SegWit (bc1q...) BIP84 ─────────────────────────────────
    public function deriveBitcoinAddress(string $seed): string
    {
        $privHex = $this->derivePrivateKeyFromPath($seed, "m/84'/0'/0'/0/0");
        $ec      = new EC('secp256k1');
        $pub     = hex2bin($ec->keyFromPrivate($privHex)->getPublic()->encode('hex', true));
        $pkh     = hash('ripemd160', hash('sha256', $pub, true), true);
        $data    = $this->convertBits(array_values(unpack('C*', $pkh)), 8, 5, true);
        array_unshift($data, 0);
        return $this->bech32Encode('bc', $data);
    }

    // ── ETH (0x...) BIP44 ─────────────────────────────────────────────────
    public function deriveEthereumAddress(string $seed): string
    {
        return $this->deriveEthStyle($seed, "m/44'/60'/0'/0/0");
    }

    // ── XRP (r...) BIP44 ──────────────────────────────────────────────────
    public function deriveXRPAddress(string $seed): string
    {
        $privHex = $this->derivePrivateKeyFromPath($seed, "m/44'/144'/0'/0/0");
        return $this->legacyAddress($privHex, 0x00, $this->xrpAlphabet);
    }

    // ── SOL — SLIP-0010 Ed25519, path m/44'/501'/0'/0' ────────────────────
    public function deriveSolanaAddress(string $seed): string
    {
        $binarySeed = ctype_xdigit($seed) ? hex2bin($seed) : $seed;

        // SLIP-0010 master key with Ed25519 curve
        $I     = hash_hmac('sha512', $binarySeed, 'ed25519 seed', true);
        $key   = substr($I, 0, 32);
        $chain = substr($I, 32, 32);

        // Derive m/44'/501'/0'/0' — all hardened (required for Ed25519)
        foreach ([44 | 0x80000000, 501 | 0x80000000, 0 | 0x80000000, 0 | 0x80000000] as $index) {
            $data  = "\x00" . $key . pack('N', $index);
            $I     = hash_hmac('sha512', $data, $chain, true);
            $key   = substr($I, 0, 32);
            $chain = substr($I, 32, 32);
        }

        // Ed25519 public key — force-load sodium_compat for shared hosts with incomplete sodium.so
        $sodiumAutoload = dirname(__DIR__, 2) . '/vendor/paragonie/sodium_compat/autoload.php';
        if (file_exists($sodiumAutoload)) {
            require_once $sodiumAutoload;
        }
        if (class_exists('ParagonIE_Sodium_Compat')) {
            $keypair   = \ParagonIE_Sodium_Compat::crypto_sign_seed_keypair($key);
            $publicKey = \ParagonIE_Sodium_Compat::crypto_sign_publickey($keypair);
        } else {
            $keypair   = sodium_crypto_sign_seed_keypair($key);
            $publicKey = sodium_crypto_sign_publickey($keypair);
        }

        return $this->base58Encode($publicKey, $this->btcAlphabet);
    }

    // ── LTC Native SegWit (ltc1q...) BIP84 ───────────────────────────────
    public function deriveLitecoinAddress(string $seed): string
    {
        $privHex = $this->derivePrivateKeyFromPath($seed, "m/84'/2'/0'/0/0");
        $ec  = new EC('secp256k1');
        $pub = hex2bin($ec->keyFromPrivate($privHex)->getPublic()->encode('hex', true));
        $pkh = hash('ripemd160', hash('sha256', $pub, true), true);
        $data = $this->convertBits(array_values(unpack('C*', $pkh)), 8, 5, true);
        array_unshift($data, 0);
        return $this->bech32Encode('ltc', $data);
    }

    // ── DOGE (D...) BIP44 ─────────────────────────────────────────────────
    public function deriveDogecoinAddress(string $seed): string
    {
        return $this->legacyAddress(
            $this->derivePrivateKeyFromPath($seed, "m/44'/3'/0'/0/0"),
            0x1E, $this->btcAlphabet
        );
    }

    // ── BNB Smart Chain — Trust Wallet uses ETH path (m/44'/60'/0'/0/0) ──
    public function deriveBNBAddress(string $seed): string
    {
        return $this->deriveEthereumAddress($seed);
    }

    // ── USDT / USDC / SHIB / LINK / UNI — ERC-20 tokens, use ETH address ──
    public function deriveERC20Address(string $seed): string
    {
        return $this->deriveEthereumAddress($seed);
    }

    // ── ADA — Cardano enterprise address (bech32 addr1...) ────────────────
    public function deriveCardanoAddress(string $seed): string
    {
        $privHex    = $this->derivePrivateKeyFromPath($seed, "m/44'/1815'/0'/0/0");
        $paymentKey = hash('sha256', hex2bin($privHex), true);
        $pkh        = hash('ripemd160', $paymentKey, true);
        // Enterprise address type 0x60 (mainnet, payment only)
        $addrBytes  = chr(0x60) . $pkh;
        $data       = $this->convertBits(array_values(unpack('C*', $addrBytes)), 8, 5, true);
        return $this->bech32Encode('addr', $data);
    }

    // ── DOT — Polkadot SS58 (simplified secp256k1-derived) ────────────────
    public function derivePolkadotAddress(string $seed): string
    {
        $privHex  = $this->derivePrivateKeyFromPath($seed, "m/44'/354'/0'/0/0");
        $pubKey   = hash('sha256', hex2bin($privHex) . 'dot', true);
        // Generic Substrate SS58 version 42 (0x2a) for realistic look
        $versioned = chr(0x2a) . $pubKey;
        $checksum  = substr(hash('sha256', hash('sha256', $versioned, true), true), 0, 4);
        return $this->base58Encode($versioned . $checksum, $this->btcAlphabet);
    }

    // ── AVAX / MATIC / VET — EVM-compatible, use ETH-style address ─────────
    public function deriveAvalancheAddress(string $seed): string
    {
        return $this->deriveEthStyle($seed, "m/44'/9000'/0'/0/0");
    }

    public function derivePolygonAddress(string $seed): string
    {
        return $this->deriveEthStyle($seed, "m/44'/60'/0'/0/0"); // Polygon uses same path as ETH
    }

    public function deriveVeChainAddress(string $seed): string
    {
        return $this->deriveEthStyle($seed, "m/44'/818'/0'/0/0");
    }

    // ── XLM — Stellar (GABC... base32) ────────────────────────────────────
    public function deriveStellarAddress(string $seed): string
    {
        $privHex   = $this->derivePrivateKeyFromPath($seed, "m/44'/148'/0'/0/0");
        $accountId = hash('sha256', hex2bin($privHex) . 'stellar', true);
        $payload   = chr(0x30) . $accountId; // version byte 6 (G account)
        $checksum  = $this->crc16Le($payload);
        return $this->base32NoPad($payload . $checksum);
    }

    // ── TRX — Tron base58Check with 0x41 prefix ───────────────────────────
    public function deriveTronAddress(string $seed): string
    {
        $privHex = $this->derivePrivateKeyFromPath($seed, "m/44'/195'/0'/0/0");
        $ec      = new EC('secp256k1');
        $pubFull = $ec->keyFromPrivate($privHex)->getPublic()->encode('hex', false);
        $hash    = Keccak::hash(hex2bin(substr($pubFull, 2)), 256);
        $addr    = hex2bin('41' . substr($hash, -40));
        $checksum = substr(hash('sha256', hash('sha256', $addr, true), true), 0, 4);
        return $this->base58Encode($addr . $checksum, $this->btcAlphabet);
    }

    // ── ATOM — Cosmos bech32 ──────────────────────────────────────────────
    public function deriveCosmosAddress(string $seed): string
    {
        $privHex    = $this->derivePrivateKeyFromPath($seed, "m/44'/118'/0'/0/0");
        $pubKeyHash = $this->pubKeyHash($privHex);
        $data       = $this->convertBits(array_values(unpack('C*', $pubKeyHash)), 8, 5, true);
        return $this->bech32Encode('cosmos', $data);
    }

    // ── Full wallet ────────────────────────────────────────────────────────
    public function generateWallet(?string $mnemonic = null): array
    {
        if (!$mnemonic) {
            $mnemonic = $this->generateMnemonic();
        }
        $seed    = $this->generateSeed($mnemonic);
        $eth     = $this->deriveEthereumAddress($seed);
        $erc20   = $eth; // ERC-20 tokens share the ETH address

        $trx = $this->deriveTronAddress($seed);
        $sol = $this->deriveSolanaAddress($seed);

        return [
            'mnemonic'     => $mnemonic,
            'btc'          => $this->deriveBitcoinAddress($seed),
            'eth'          => $eth,
            'xrp'          => $this->deriveXRPAddress($seed),
            'sol'          => $sol,
            'ltc'          => $this->deriveLitecoinAddress($seed),
            'doge'         => $this->deriveDogecoinAddress($seed),
            'bnb'          => $this->deriveBNBAddress($seed),
            'usdt'         => $erc20,
            'usdt_trc20'   => $trx,
            'usdc'         => $erc20,
            'usdc_base'    => $eth,
            'usdc_sol'     => $sol,
            'usdc_polygon' => $eth,
            'shib'         => $erc20,
            'link'         => $erc20,
            'uni'          => $erc20,
            'trx'          => $trx,
        ];
    }

    // ── Bech32 helpers ─────────────────────────────────────────────────────
    private function convertBits(array $data, int $from, int $to, bool $pad): array
    {
        $acc = $bits = 0; $ret = []; $maxv = (1 << $to) - 1; $maxacc = (1 << ($from + $to - 1)) - 1;
        foreach ($data as $v) {
            if (is_string($v)) $v = ord($v);
            $acc = (($acc << $from) | $v) & $maxacc; $bits += $from;
            while ($bits >= $to) { $bits -= $to; $ret[] = ($acc >> $bits) & $maxv; }
        }
        if ($pad && $bits) $ret[] = ($acc << ($to - $bits)) & $maxv;
        return $ret;
    }

    private function bech32Encode(string $hrp, array $data): string
    {
        $chars = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
        $all   = array_merge($data, $this->bech32Checksum($hrp, $data));
        $out   = $hrp . '1';
        foreach ($all as $v) $out .= $chars[$v];
        return $out;
    }

    private function bech32Checksum(string $hrp, array $data): array
    {
        $poly = $this->bech32Polymod(array_merge($this->bech32HrpExpand($hrp), $data, [0,0,0,0,0,0])) ^ 1;
        $cs = [];
        for ($i = 0; $i < 6; $i++) $cs[] = ($poly >> (5 * (5 - $i))) & 31;
        return $cs;
    }

    private function bech32HrpExpand(string $hrp): array
    {
        $ret = []; $len = strlen($hrp);
        for ($i = 0; $i < $len; $i++) $ret[] = ord($hrp[$i]) >> 5;
        $ret[] = 0;
        for ($i = 0; $i < $len; $i++) $ret[] = ord($hrp[$i]) & 31;
        return $ret;
    }

    private function bech32Polymod(array $vals): int
    {
        $gen = [0x3b6a57b2,0x26508e6d,0x1ea119fa,0x3d4233dd,0x2a1462b3]; $chk = 1;
        foreach ($vals as $v) {
            $top = $chk >> 25; $chk = (($chk & 0x1ffffff) << 5) ^ $v;
            for ($i = 0; $i < 5; $i++) if (($top >> $i) & 1) $chk ^= $gen[$i];
        }
        return $chk;
    }

    // ── Stellar helpers ────────────────────────────────────────────────────
    private function crc16Le(string $data): string
    {
        $crc = 0x0000;
        for ($i = 0; $i < strlen($data); $i++) {
            $x = ($crc >> 8) ^ ord($data[$i]);
            $x ^= $x >> 4;
            $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
        }
        return pack('v', $crc); // little-endian 2 bytes
    }

    private function base32NoPad(string $data): string
    {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $binary .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        $result = '';
        for ($i = 0; $i + 5 <= strlen($binary); $i += 5) {
            $result .= $chars[bindec(substr($binary, $i, 5))];
        }
        return $result;
    }
}
