<?php

return [
    'user.login' => [
        [library\event\User::class, 'login'],
        // ...其它事件处理函数...
    ],
    'user.register' => [
        [library\event\User::class, 'register'],
    ],
    'user.logout' => [
        [library\event\User::class, 'logout'],
    ],
    'user.updateUserLevel' => [
        [library\event\User::class, 'updateUserLevel'],
    ],
    'user.finishRechargeOrder'=> [
        [library\event\User::class, 'finishRechargeOrder'],
    ],
    'user.finishProjectOrder'=> [
        [library\event\User::class, 'finishProjectOrder'],
    ],
    'user.calcProjectOrderCommission'=> [
        [library\event\User::class, 'calcProjectOrderCommission'],
    ],
//    'user.*' => [
//        [library\event\User::class, 'deal']
//    ],
];
