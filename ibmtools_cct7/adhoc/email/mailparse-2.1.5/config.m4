dnl
dnl $Id: config.m4,v 1.5 2002/04/06 14:32:34 wez Exp $
dnl

PHP_ARG_ENABLE(mailparse, whether to enable mailparse support,
[  --enable-mailparse      Enable mailparse support.])

if test "$PHP_MAILPARSE" != "no"; then
  if test "$ext_shared" != "yes" && test "$enable_mbstring" != "yes"; then
	  AC_MSG_WARN(Activating mbstring)
	  enable_mbstring=yes
  fi
  PHP_NEW_EXTENSION(mailparse, 	mailparse.c php_mailparse_mime.c php_mailparse_rfc822.c, $ext_shared)

  PHP_ADD_MAKEFILE_FRAGMENT

fi

