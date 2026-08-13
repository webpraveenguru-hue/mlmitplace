<?php

if (!defined('OK_LOADME')) {
    die('o o p s !');
}

// ----------------
// Array of Payment Options
// ----------------
$avalpaymentopt_array = array(
    'system' => 'System',
    'ewalletlabel' => 'EWallet',
    'bankacc' => 'Bank Account',
    'manualpayipn' => 'manualpayname',
    'epin' => 'ePin',
    'other' => 'Other'
);

// ----------------
// Array of Payment Icon or Logo
// ----------------
$avalpaygateicon_array = array(
    'bankacc' => "<i class='fa fa-university fa-fw'></i>",
    'ewalletlabel' => "<i class='fa fa-wallet fa-fw'></i>",
    'manualpayipn' => "<i class='fa fa-handshake fa-fw'></i>",
);

// ----------------
// Array of Withdraw Options
// ----------------
$avalwithdrawgate_array = array(
    'bankacc' => 'Bank Account',
    'manualpayipn' => 'manualpayname',
);

// ----------------
// Array of Admin Pages
// ----------------
$avaladminpage_array = array(
    'dashboard' => 1,
    'userlist' => 1,
    'getuser' => 1,
    'kyclist' => 1,
    'historylist' => 1,
    'saleslist' => 1,
    'pointlist' => 1,
    'pointrwd' => 1,
    'epinlist' => 1,
    'withdrawlist' => 1,
    'genealogylist' => 1,
    'digifile' => 1,
    'digicontent' => 1,
    'managegroup' => 1,
    'mmbanner' => 1,
    'getstart' => 1,
    'homepg' => 1,
    'termscon' => 1,
    'notifylist' => 1,
    'generalcfg' => 1,
    'payplancfg' => 1,
    'paymentopt' => 1,
    'newsletter' => 1,
    'ranklist' => 1,
    'languagelist' => 1,
    'itemlist' => 1,
    'updates' => 1
);

// ----------------
// Array of Member Pages
// ----------------
$avalmemberpage_array = array(
    'dashboard' => 1,
    'getstarted' => 1,
    'planreg' => 1,
    'planpay' => 1,
    'userlist' => 1,
    'getuser' => 1,
    'historylist' => 1,
    'orderlist' => 1,
    'withdrawreq' => 1,
    'genealogyview' => 1,
    'digiload' => 1,
    'digiview' => 1,
    'mmbanner' => 1,
    'accountcfg' => 1,
    'store' => 1,
    'orderpay' => 1,
    'mysaleslist' => 1,
    'mydigicontent' => 1,
    'myitemlist' => 1,
    'feedback' => 1
);

// ----------------
// Array of Store pages
// ----------------
$avalstorepage_array = array(
    'saleslist' => 1,
    'itemlist' => 1,
    'orderlist' => 1,
    'store' => 1,
    'orderpay' => 1
);

// ----------------
// Array of Interval
// ----------------
$rangeinterval_array = array(
    '' => '/ Onetime',
    '1m' => '/ Month',
    '3m' => '/ 3 Month',
    '1y' => '/ Year',
);

// ----------------
// Array of Item Status
// ----------------
$itemstatus_array = array(
    0 => 'Disable',
    1 => 'Enable',
    2 => 'Private',
    7 => 'Hidden',
    8 => 'Archive',
    9 => 'Waiting Approval',
    13 => 'Suspended',
);

// ----------------
// Array of Point Rewards
// ----------------
$prtypeopt_array = array(
    'file' => "Digital Download",
    'page' => "Digital Content",
    'cash' => "Cash",
    'custom' => "Other"
);

// ----------------
// Array of Theme Colors
// ----------------
$colortheme_array = array(
    '' => '007BFF',
    'v1' => '6631BD',
    'p1' => 'F183A8',
    'b1' => '237289',
    'g1' => '428810',
    'y1' => 'DAB658',
    'o1' => 'FC6C35',
    'r1' => 'D42C5E',
    'blue1' => '01489A',
    'green1' => '167135',
    'dark1' => '262527',
    'brown1' => '563407',
    'viole1' => '9c3993',
);

// ----------------
// Array of Country
// ----------------
$country_array = array(
    'AF' => 'AFGHANISTAN',
    'AL' => 'ALBANIA',
    'DZ' => 'ALGERIA',
    'AS' => 'AMERICAN SAMOA',
    'AO' => 'ANGOLA',
    'AR' => 'ARGENTINA',
    'AW' => 'ARUBA',
    'AU' => 'AUSTRALIA',
    'AZ' => 'AZERBAIJAN',
    'BS' => 'BAHAMAS',
    'BD' => 'BANGLADESH',
    'BY' => 'BELARUS',
    'BO' => 'BOLIVIA',
    'BA' => 'BOSNIA HERZEGOVINA',
    'BW' => 'BOTSWANA',
    'BR' => 'BRAZIL',
    'BN' => 'BRUNEI DARUSSALAM',
    'BI' => 'BURUNDI',
    'KH' => 'CAMBODIA',
    'CA' => 'CANADA',
    'KY' => 'CAYMAN ISLANDS',
    'CF' => 'CENTRAL AFRICAN REPUBLIC',
    'CL' => 'CHILE',
    'CC' => 'COCOS KEELING ISLANDS',
    'CO' => 'COLOMBIA',
    'KM' => 'COMOROS',
    'CG' => 'CONGO',
    'CK' => 'COOK ISLANDS',
    'CR' => 'COSTA RICA',
    'CI' => 'COTE D IVOIRE',
    'CU' => 'CUBA',
    'CY' => 'CYPRUS',
    'CZ' => 'CZECH REPUBLIC',
    'DM' => 'DOMINICA',
    'EC' => 'ECUADOR',
    'EG' => 'EGYPT',
    'SV' => 'EL SALVADOR',
    'ET' => 'ETHIOPIA',
    'FR' => 'FRANCE',
    'GA' => 'GABON',
    'GM' => 'GAMBIA',
    'DE' => 'GERMANY',
    'GH' => 'GHANA',
    'GI' => 'GIBRALTAR',
    'GR' => 'GREECE',
    'HT' => 'HAITI',
    'HK' => 'HONGKONG',
    'IS' => 'ICELAND',
    'IN' => 'INDIA',
    'ID' => 'INDONESIA',
    'IR' => 'IRAN',
    'IQ' => 'IRAQ',
    'IE' => 'IRELAND',
    'IT' => 'ITALY',
    'JP' => 'JAPAN',
    'JO' => 'JORDAN',
    'KZ' => 'KAZAKSTAN',
    'KE' => 'KENYA',
    'KP' => 'KOREA NORTH',
    'KR' => 'KOREA SOUTH',
    'KW' => 'KUWAIT',
    'KG' => 'KYRGYZSTAN',
    'LV' => 'LATVIA',
    'LB' => 'LEBANON',
    'LY' => 'LIBYA',
    'MO' => 'MACAU',
    'MK' => 'MACEDONIA',
    'MG' => 'MADAGASCAR',
    'MY' => 'MALAYSIA',
    'MV' => 'MALDIVES',
    'MX' => 'MEXICO',
    'MD' => 'MOLDOVA',
    'MA' => 'MOROCCO',
    'MZ' => 'MOZAMBIQUE',
    'NA' => 'NAMIBIA',
    'NP' => 'NEPAL',
    'NL' => 'NETHERLANDS',
    'NZ' => 'NEW ZEALAND',
    'NI' => 'NICARAGUA',
    'NG' => 'NIGERIA',
    'OM' => 'OMAN',
    'PK' => 'PAKISTAN',
    'PS' => 'PALESTINE',
    'PA' => 'PANAMA',
    'PE' => 'PERU',
    'PH' => 'PHILIPPINES',
    'PL' => 'POLAND',
    'PT' => 'PORTUGAL',
    'PR' => 'PUERTO RICO',
    'QA' => 'QATAR',
    'RU' => 'RUSSIAN FEDERATION',
    'RW' => 'RWANDA',
    'SA' => 'SAUDI ARABIA',
    'SN' => 'SENEGAL',
    'SC' => 'SEYCHELLES',
    'SL' => 'SIERRA LEONE',
    'SG' => 'SINGAPORE',
    'SO' => 'SOMALIA',
    'ZA' => 'SOUTH AFRICA',
    'GS' => 'SOUTH GEORGIA',
    'ES' => 'SPAIN',
    'LK' => 'SRI LANKA',
    'SD' => 'SUDAN',
    'SR' => 'SURINAME',
    'SZ' => 'SWAZILAND',
    'SE' => 'SWEDEN',
    'CH' => 'SWITZERLAND',
    'TW' => 'TAIWAN',
    'TJ' => 'TAJIKISTAN',
    'TZ' => 'TANZANIA',
    'TH' => 'THAILAND',
    'TK' => 'TOKELAU',
    'TT' => 'TRINIDAD AND TOBAGO',
    'TN' => 'TUNISIA',
    'TR' => 'TURKEY',
    'TM' => 'TURKMENISTAN',
    'UG' => 'UGANDA',
    'AE' => 'UNITED ARAB EMIRATES',
    'GB' => 'UNITED KINGDOM',
    'US' => 'UNITED STATES',
    'UZ' => 'UZBEKISTAN',
    'VE' => 'VENEZUELA',
    'YE' => 'YEMEN',
    'YU' => 'YUGOSLAVIA',
    'ZM' => 'ZAMBIA',
    'ZW' => 'ZIMBABWE',
    '' => 'ANOTHER'
);

// ----------------------------
// Array of Website Category
// ----------------------------
$webcategory_array = array(
    'Business General' => 'Business General',
    'Affiliate and Reseller Programs' => 'Affiliate and Reseller Programs',
    'Domain and Hosting' => 'Domain and Hosting',
    'Business and Finance' => 'Business and Finance',
    'Directories and Search Engines' => 'Directories and Search Engines',
    'MLM Related Programs' => 'MLM Related Programs',
    'Career and Education' => 'Career and Education',
    'Marketing and Advertising' => 'Marketing and Advertising',
    'Computers and Technology' => 'Computers and Technology',
    'Health and Sports' => 'Health and Sports',
    'Shopping and Merchants' => 'Shopping and Merchants',
    'Home and Lifestyle' => 'Home and Lifestyle',
    'Entertainment' => 'Entertainment',
    'Charity and Donations' => 'Charity and Donations',
    'Other' => 'Other'
);

// ----------------
// Footer Values
// ----------------
$crftthisyear = date("Y") . ' <div class="bullet"></div>';
$crftpowbyicoyear = (defined('FOOTER_BYTEXT') && FOOTER_BYTEXT != '') ? FOOTER_BYTEXT : '<i class="fa fa-fw fa-heart"></i>' . $crftthisyear;
