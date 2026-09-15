<x-mail::message>
# 账号注销申请确认

您好：

我们收到了您的账号注销申请。请注意以下重要信息：

## 账号删除倒计时

您的账号将在 **{{ $deletionScheduledAt }}** 后被永久删除。

在此期间，您可以随时点击下方的"恢复账号"按钮取消注销。

## 将要删除的数据

- {{ $user->resumes()->count() }} 份简历
- {{ $user->interviewSessions()->count() }} 条面试记录
- {{ $user->jobApplications()->count() }} 条求职申请
- 所有个人设置和历史数据

**注意：删除后数据无法恢复！**

<x-mail::button :url="$recoveryUrl" color="primary">
恢复账号（取消注销）
</x-mail::button>

如果您没有发起此操作，请立即修改密码并联系我们。

感谢您的使用，祝您前程似锦！

{{ config('app.name') }} 团队
</x-mail::message>
