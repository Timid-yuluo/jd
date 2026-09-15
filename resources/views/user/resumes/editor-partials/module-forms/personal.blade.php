<template x-if="mod.type === 'personal'">
                            <div class="editor-form-stack">
                                <div class="editor-field-surface">
                                    <div class="editor-surface-head">
                                        <div>
                                            <div class="editor-surface-title"><i class="ti ti-id"></i>基础资料</div>
                                            <div class="editor-surface-note">建议优先填写姓名、电话、邮箱和所在城市。</div>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label form-label-sm">姓名</label>
                                            <input type="text" class="form-control form-control-sm" x-model="mod.data.name" placeholder="你的真实姓名" data-field="name">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm">电话</label>
                                            <input type="tel" class="form-control form-control-sm" x-model="mod.data.phone" placeholder="常用手机号码" maxlength="20" data-field="phone">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm">邮箱</label>
                                            <input type="email" class="form-control form-control-sm" x-model="mod.data.email" placeholder="常用邮箱地址" data-field="email">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm">所在地</label>
                                            <input type="text" class="form-control form-control-sm" x-model="mod.data.location" placeholder="例如：北京" data-field="location">
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label form-label-sm">性别</label>
                                            <input type="text" class="form-control form-control-sm" x-model="mod.data.gender" placeholder="例如：男/女" data-field="gender">
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label form-label-sm">生日</label>
                                            <input type="text" class="form-control form-control-sm" x-model="mod.data.birthday" placeholder="例如：2004-06-01" data-field="birthday">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm">微信</label>
                                            <input type="text" class="form-control form-control-sm" x-model="mod.data.wechat" placeholder="微信号" data-field="wechat">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm">GitHub</label>
                                            <input type="text" class="form-control form-control-sm" x-model="mod.data.github" placeholder="GitHub 用户名或链接" data-field="github">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label form-label-sm">作品集</label>
                                            <input type="text" class="form-control form-control-sm" x-model="mod.data.website" placeholder="个人网站 / 作品集链接" data-field="website">
                                        </div>
                                    </div>
                                    <div class="editor-inline-helper" x-text="moduleAssistText(mod)"></div>
                                </div>

                                <div class="editor-field-surface">
                                    <div class="editor-surface-head">
                                        <div>
                                            <div class="editor-surface-title"><i class="ti ti-list-plus"></i>自定义字段</div>
                                            <div class="editor-surface-note">可补充 QQ、政治面貌、求职状态等额外信息。</div>
                                        </div>
                                    </div>
                                    <div class="editor-item-list">
                                        <template x-for="(field, fieldIndex) in personalCustomFields(mod)" :key="`personal-custom-${mod._key}-${fieldIndex}`">
                                            <div class="row g-2 align-items-center mb-2">
                                                <div class="col-4">
                                                    <input type="text" class="form-control form-control-sm"
                                                        x-model="mod.data.custom_fields[fieldIndex].label"
                                                        placeholder="字段名（如：QQ）">
                                                </div>
                                                <div class="col-7">
                                                    <input type="text" class="form-control form-control-sm"
                                                        x-model="mod.data.custom_fields[fieldIndex].value"
                                                        placeholder="字段值">
                                                </div>
                                                <div class="col-1 d-flex align-items-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removePersonalCustomField(index, fieldIndex)">
                                                        <i class="ti ti-x"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm mt-1" @click="addPersonalCustomField(index)">
                                        <i class="ti ti-plus me-1"></i>添加字段
                                    </button>
                                </div>

                                <div class="editor-field-surface">
                                    <div class="editor-surface-head">
                                        <div>
                                            <div class="editor-surface-title"><i class="ti ti-message-2"></i>个人简介</div>
                                            <div class="editor-surface-note">一句话概括你的核心优势与职业定位。</div>
                                        </div>
                                    </div>
                                    <textarea class="form-control form-control-sm" x-model="mod.data.content" rows="3" placeholder="例如：聚焦品牌营销经理方向，结果导向，具备快速学习与跨团队协作能力。" data-field="content"></textarea>
                                </div>

                                <div class="editor-field-surface">
                                    <div class="editor-surface-head">
                                        <div>
                                            <div class="editor-surface-title"><i class="ti ti-user-circle"></i>头像</div>
                                            <div class="editor-surface-note">头像会同步显示在右侧预览，建议使用清晰证件照。</div>
                                        </div>
                                    </div>
                                    <div class="editor-avatar-panel">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="editor-avatar-preview">
                                                <img x-show="mod.data.avatar" :src="mod.data.avatar" alt="头像预览" style="width: 100%; height: 100%; object-fit: cover;">
                                                <div x-show="!mod.data.avatar" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 20px;">
                                                    <i class="ti ti-user"></i>
                                                </div>
                                            </div>
                                            <div class="small text-muted">可上传 JPG、PNG、WEBP 等格式。</div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="file" :id="'avatar-upload-' + mod._key" accept="image/*" style="display: none;" @change="uploadAvatar($event, index)">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="document.getElementById('avatar-upload-' + mod._key).click()">
                                                <i class="ti ti-upload me-1"></i>上传头像
                                            </button>
                                            <button type="button" class="btn btn-ghost-danger btn-sm" x-show="mod.data.avatar" @click="mod.data.avatar = ''">
                                                <i class="ti ti-trash me-1"></i>移除
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
