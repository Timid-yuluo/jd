@component('mail::message')
# 您的反馈已收到回复

感谢您提交的反馈，我们的团队已对您的问题进行了回复。

---

**反馈标题：** {{ $feedback->title }}

**反馈类型：** {{ $feedback->getCategoryLabel() }}

---

### 回复内容

{{ $reply->content }}

---

@if($feedback->status === 'closed')
该反馈已关闭。如有其他问题，欢迎随时提交新的反馈。
@else
如需继续沟通，可以在原反馈中追加回复。
@endif

@component('mail::button', ['url' => url('/feedback/' . $feedback->getKey())])
查看反馈详情
@endcomponent

---

此邮件由系统自动发送，请勿直接回复。
@endcomponent
