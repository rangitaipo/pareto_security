<?php
class Logjam_Filter {
    /**
     * decoder
     *
     * @param string $input
     * @return string
     */
    public static function decoder( $input = '' ) {
        for ( $x=0; $x <= 10; $x++ ) {
            $input = rawurldecode( urldecode( $input ) );
            $input = str_replace( chr( 0 ), '', $input );
        }
        return $input;
    }
    /**
     * decoder
     *
     * @param string $input
     * @return bool True on success or false on failure
     */    
    public static function logjam_check( $input = '' ) {
        # Attempts to exploit CVE-2021-44228
        # Standard
        $triggers = 'ldap|rmi|dns|nis|iiop|corba|nds|http';
        $input = self::decoder ( $input );
        $input = rawurldecode( urldecode( str_replace( chr( 0 ), '', $input ) ) );
        if ( false !== strpos( $input, 'jndi' ) && false !== ( bool ) preg_match( "/$triggers/i", $input ) ) {
                 return true;
        }
        # Deal with variants
        if ( substr_count( $input, '$' ) >= 2 &&
             substr_count( $input, '{' ) >= 2 &&
             substr_count( $input, '}' ) >= 2 &&
             substr_count( $input, ':' ) >= 3 ) {
             $list = array( 'lower', 'upper' );
             $input = str_replace( $list, '', $input );
             $input = preg_replace( "/[^a-zA-Z]/i", "", $input );
             if ( false !== strpos( $input, 'jndi' ) && false !== ( bool ) preg_match( "/$triggers/i", $input ) ) {
                  return true;
            }
        }
        return false;
    }
}
?>