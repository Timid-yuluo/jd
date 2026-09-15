@php
    $errorCode = '403';
    $errorTitle = '禁止访问';
    $errorMessage = '您没有权限访问此页面，请联系管理员获取权限。';
    $errorIcon = 'ti ti-lock';
    $errorCssFile = 'errors-403';
    $errorAction = '<a href="/" class="error-link"><i class="ti ti-home me-2"></i>返回首页</a> <a href="javascript:history.back()" class="error-link"><i class="ti ti-arrow-left me-2"></i>返回上一页</a>';
@endphp
@extends('layouts.error')
