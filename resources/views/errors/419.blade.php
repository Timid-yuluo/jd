@php
    $errorCode = '419';
    $errorTitle = '页面已过期';
    $errorMessage = '您的会话已过期，请刷新页面后重试。';
    $errorIcon = 'ti ti-clock';
    $errorCssFile = 'errors-419';
    $errorAction = '<a href="javascript:history.back()" class="error-link"><i class="ti ti-arrow-left me-2"></i>返回上一页</a> <a href="#" data-action="reload" class="error-link"><i class="ti ti-refresh me-2"></i>刷新页面</a>';
@endphp
@extends('layouts.error')
