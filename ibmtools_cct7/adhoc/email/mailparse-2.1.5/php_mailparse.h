/*
   +----------------------------------------------------------------------+
   | PHP Version 4                                                        |
   +----------------------------------------------------------------------+
   | Copyright (c) 1997-2004 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Wez Furlong <wez@thebrainroom.com>                           |
   | Credit also given to Double Precision Inc. who wrote the code that   |
   | the support routines for this extension were based upon.             |
   +----------------------------------------------------------------------+
 */
/* $Id: php_mailparse.h,v 1.19 2009/03/03 22:06:59 shire Exp $ */

#ifndef PHP_MAILPARSE_H
#define PHP_MAILPARSE_H

extern zend_module_entry mailparse_module_entry;
#define phpext_mailparse_ptr &mailparse_module_entry

#define PHP_MAILPARSE_VERSION "2.1.5"

#ifdef PHP_WIN32
#define PHP_MAILPARSE_API __declspec(dllexport)
#else
#define PHP_MAILPARSE_API
#endif

#ifndef  Z_SET_REFCOUNT_P
# if PHP_MAJOR_VERSION < 6 && (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION < 3)
#  define Z_SET_REFCOUNT_P(pz, rc)  (pz)->refcount = rc 
#  define Z_UNSET_ISREF_P(pz) (pz)->is_ref = 0 
#  define Z_DELREF_P(pz) (pz)->refcount--
#  define Z_REFCOUNT_P(pz) (pz)->refcount
#  define Z_ISREF_P(pz) (pz)->is_ref
#  define Z_ADDREF_P(pz) (pz)->refcount++
#  define Z_SET_ISREF_TO_P(pz, isref) (pz)->is_ref = isref
# endif
#endif

PHP_MINIT_FUNCTION(mailparse);
PHP_MSHUTDOWN_FUNCTION(mailparse);
PHP_RINIT_FUNCTION(mailparse);
PHP_RSHUTDOWN_FUNCTION(mailparse);
PHP_MINFO_FUNCTION(mailparse);

PHP_FUNCTION(mailparse_msg_parse_file);
PHP_FUNCTION(mailparse_msg_get_part);
PHP_FUNCTION(mailparse_msg_get_structure);
PHP_FUNCTION(mailparse_msg_get_part_data);
PHP_FUNCTION(mailparse_msg_extract_part);
PHP_FUNCTION(mailparse_msg_extract_part_file);
PHP_FUNCTION(mailparse_msg_extract_whole_part_file);

PHP_FUNCTION(mailparse_msg_create);
PHP_FUNCTION(mailparse_msg_free);
PHP_FUNCTION(mailparse_msg_parse);
PHP_FUNCTION(mailparse_msg_parse_file);

PHP_FUNCTION(mailparse_msg_find);
PHP_FUNCTION(mailparse_msg_getstructure);
PHP_FUNCTION(mailparse_msg_getinfo);
PHP_FUNCTION(mailparse_msg_extract);
PHP_FUNCTION(mailparse_msg_extract_file);
PHP_FUNCTION(mailparse_rfc822_parse_addresses);
PHP_FUNCTION(mailparse_determine_best_xfer_encoding);
PHP_FUNCTION(mailparse_stream_encode);
PHP_FUNCTION(mailparse_uudecode_all);

PHP_FUNCTION(mailparse_test);

PHP_MAILPARSE_API int php_mailparse_le_mime_part(void);

/* mimemessage object */
PHP_FUNCTION(mailparse_mimemessage);
PHP_FUNCTION(mailparse_mimemessage_get_parent);
PHP_FUNCTION(mailparse_mimemessage_get_child);
PHP_FUNCTION(mailparse_mimemessage_get_child_count);
PHP_FUNCTION(mailparse_mimemessage_extract_headers);
PHP_FUNCTION(mailparse_mimemessage_extract_body);
PHP_FUNCTION(mailparse_mimemessage_enum_uue);
PHP_FUNCTION(mailparse_mimemessage_extract_uue);
PHP_FUNCTION(mailparse_mimemessage_remove);
PHP_FUNCTION(mailparse_mimemessage_add_child);

/* PHP 4.3.4  moved the mbfilter header around */
#if PHP_MAJOR_VERSION == 4 && ((PHP_MINOR_VERSION < 3) || (PHP_MINOR_VERSION == 3 && PHP_RELEASE_VERSION < 4))
# include "ext/mbstring/mbfilter.h"
# define MAILPARSE_MBSTRING_TSRMLS_CC	TSRMLS_CC
# define MAILPARSE_MBSTRING_TSRMLS_DC	TSRMLS_DC
# define MAILPARSE_MBSTRING_TSRMLS_FETCH_IF_BRAIN_DEAD()	/* sanity */
#else
# include "ext/mbstring/libmbfl/mbfl/mbfilter.h"
/* ugh, even worse, they changed the signature of the API and made it
 * really slow for threaded PHP builds */
# define MAILPARSE_MBSTRING_TSRMLS_CC	/* pain */
# define MAILPARSE_MBSTRING_TSRMLS_DC	/* pain */
# define MAILPARSE_MBSTRING_TSRMLS_FETCH_IF_BRAIN_DEAD()	TSRMLS_FETCH()
#endif

#include "php_mailparse_rfc822.h"
#include "php_mailparse_mime.h"

#define MAILPARSE_BUFSIZ		4096
ZEND_BEGIN_MODULE_GLOBALS(mailparse)
    char * def_charset;	/* default charset for use in (re)writing mail */
ZEND_END_MODULE_GLOBALS(mailparse);

extern ZEND_DECLARE_MODULE_GLOBALS(mailparse);


#ifdef ZTS
#define MAILPARSEG(v) TSRMG(mailparse_globals_id, zend_mailparse_globals *, v)
#else
#define MAILPARSEG(v) (mailparse_globals.v)
#endif

#endif


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim: sw=4 ts=4
 */
