<?php
/**
 * @brief
 *
 * @author tlanyan<tlanyan@hotmail.com>
 * @link http://tlanyan.me
 */
/* vim: set ts=4; set sw=4; set ss=4; set expandtab; */

namespace tlanyan;

use yii\base\InvalidConfigException;

class RsaKeyNotSetException extends InvalidConfigException
{
    public function getName()
    {
        return 'rsa private key not set!';
    }
}
