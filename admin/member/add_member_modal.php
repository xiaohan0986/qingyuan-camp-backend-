<style>
#addUserDrawer .form-container {
    max-width: 480px;
    margin: 0 auto;
}
#addUserDrawer .section-header {
    font-size: 15px;
    font-weight: 600;
    color: #262626;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
    padding-left: 12px;
    position: relative;
}
#addUserDrawer .section-header::before {
    content: '';
    position: absolute;
    left: 0;
    top: 2px;
    bottom: 12px;
    width: 3px;
    background: #1890ff;
    border-radius: 2px;
}
#addUserDrawer .form-field {
    margin-bottom: 18px;
}
#addUserDrawer .field-label {
    font-size: 13px;
    color: #595959;
    font-weight: 500;
    display: block;
    margin-bottom: 6px;
}
#addUserDrawer .field-label .required {
    color: #ff4d4f;
}
#addUserDrawer .field-input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
    transition: border-color 0.25s, box-shadow 0.25s;
    outline: none;
    background: #fff;
}
#addUserDrawer .field-input:focus {
    border-color: #1890ff;
    box-shadow: 0 0 0 2px rgba(24,144,255,0.1);
}
#addUserDrawer .field-input::placeholder {
    color: #bfbfbf;
}
#addUserDrawer .avatar-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 0;
}
#addUserDrawer .avatar-trigger {
    position: relative;
    cursor: pointer;
    border-radius: 50%;
    transition: opacity 0.25s;
}
#addUserDrawer .avatar-trigger:hover {
    opacity: 0.85;
}
#addUserDrawer .avatar-trigger:hover .avatar-overlay {
    opacity: 1;
}
#addUserDrawer .avatar-preview {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #f0f0f0;
    display: block;
}
#addUserDrawer .avatar-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.25s;
}
#addUserDrawer .avatar-overlay svg {
    width: 28px;
    height: 28px;
    color: white;
}
#addUserDrawer .avatar-hint {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 10px;
}
#addUserDrawer .phone-row {
    display: flex;
    gap: 8px;
}
#addUserDrawer .phone-row .field-input {
    flex: 1;
}
#addUserDrawer .code-btn {
    flex-shrink: 0;
    padding: 9px 16px;
    border: 1px solid #1890ff;
    border-radius: 8px;
    background: white;
    color: #1890ff;
    font-size: 13px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.25s;
}
#addUserDrawer .code-btn:hover {
    background: #e6f7ff;
}
#addUserDrawer .code-btn:disabled {
    background: #f5f5f5;
    color: #999;
    border-color: #d9d9d9;
    cursor: not-allowed;
}
#addUserDrawer .form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    margin-top: 24px;
}
#addUserDrawer .btn-cancel {
    padding: 9px 20px;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    background: white;
    color: #595959;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.25s;
}
#addUserDrawer .btn-cancel:hover {
    color: #262626;
    border-color: #bfbfbf;
}
#addUserDrawer .btn-submit {
    padding: 9px 24px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #1890ff, #096dd9);
    color: white;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.25s;
    box-shadow: 0 2px 8px rgba(24,144,255,0.35);
}
#addUserDrawer .btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(24,144,255,0.4);
}
#addUserDrawer .btn-submit:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
}
#addUserDrawer .pwd-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
#addUserDrawer .pwd-input {
    padding-right: 40px;
}
#addUserDrawer .pwd-toggle {
    position: absolute;
    right: 3px;
    top: 50%;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: background 0.2s;
    color: #8c8c8c;
    z-index: 1;
}
#addUserDrawer .pwd-toggle:hover {
    background: #f5f5f5;
    color: #595959;
}
#addUserDrawer .pwd-toggle svg {
    width: 18px;
    height: 18px;
    pointer-events: none;
}
#addUserDrawer .field-label.error {
    color: #ff4d4f;
}
#addUserDrawer .field-input.error {
    border-color: #ff4d4f;
    box-shadow: 0 0 0 2px rgba(255,77,79,0.1);
}
#addUserDrawer .field-input.error:focus {
    border-color: #ff4d4f;
    box-shadow: 0 0 0 2px rgba(255,77,79,0.15);
}
#addUserDrawer .pwd-error {
    font-size: 12px;
    color: #ff4d4f;
    margin-top: 4px;
    display: none;
    line-height: 1.4;
}
#addUserDrawer .pwd-error.show {
    display: block;
}
</style>

<div class="detail-drawer-overlay" id="addDrawerOverlay" onclick="closeAddUserDrawer(event)"></div>
<div class="detail-drawer" id="addUserDrawer">
    <div class="detail-drawer-header">
        <h3 class="detail-drawer-title">新增用户</h3>
        <div style="display:flex;align-items:center;gap:8px;">
            <button class="detail-drawer-close" onclick="closeAddUserDrawer()">&#10005;</button>
        </div>
    </div>
    <div class="detail-drawer-body">
        <form id="addUserForm" onsubmit="return submitAddUser(event)">
            <div class="form-container">

                <!-- 头像 -->
                <div class="detail-section">
                    <h4 class="section-header">头像</h4>
                    <div class="avatar-wrap">
                        <div class="avatar-trigger" onclick="document.getElementById('avatarInput').click()">
                            <img id="avatarPreview" src="../images/avatar-placeholder.svg" alt="头像" class="avatar-preview">
                            <div class="avatar-overlay">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            </div>
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display:none;" onchange="uploadAvatar(this)">
                        <input type="hidden" name="avatar" id="avatarUrl" value="">
                        <div class="avatar-hint">点击上传头像，支持 JPG / PNG，不超过 5MB</div>
                    </div>
                </div>

                <!-- 基本信息 -->
                <div class="detail-section">
                    <h4 class="section-header">基本信息</h4>
                    <div class="form-field">
                        <label class="field-label">昵称 <span class="required">*</span></label>
                        <input type="text" name="nickname" required class="field-input" placeholder="请输入用户昵称">
                    </div>
                    <div class="form-field">
                        <label class="field-label" id="pwdLabel">密码 <span class="required">*</span></label>
                        <div class="pwd-wrapper">
                            <input type="password" name="password" id="passwordInput" required class="field-input pwd-input" placeholder="请设置登录密码（至少 6 位）" oninput="checkPwdMatch()">
                            <button type="button" class="pwd-toggle" onclick="togglePwdVisibility('passwordInput', this)" tabindex="-1">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-field" id="confirmPwdField">
                        <label class="field-label" id="confirmPwdLabel">确认密码 <span class="required">*</span></label>
                        <div class="pwd-wrapper">
                            <input type="password" name="confirm_password" id="confirmPwdInput" required class="field-input pwd-input" placeholder="请再次输入密码" oninput="checkPwdMatch()">
                            <button type="button" class="pwd-toggle" onclick="togglePwdVisibility('confirmPwdInput', this)" tabindex="-1">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <div class="pwd-error" id="pwdErrorMsg">密码输入不一致，请修改</div>
                    </div>
                    <div class="form-field">
                        <label class="field-label">校区</label>
                        <select name="campus" class="field-input" style="background:white;">
                            <option value="">请选择校区</option>
                            <option value="青园总校">青园总校</option>
                            <option value="青园东校区">青园东校区</option>
                            <option value="青园西校区">青园西校区</option>
                            <option value="青园南校区">青园南校区</option>
                            <option value="其他">其他</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="field-label">简介</label>
                        <textarea name="bio" class="field-input" rows="3" placeholder="用户简介或备注信息（选填）" style="resize:vertical;"></textarea>
                    </div>
                </div>

                <!-- 手机验证 -->
                <div class="detail-section">
                    <h4 class="section-header">手机验证</h4>
                    <div class="form-field">
                        <label class="field-label">手机号 <span class="required">*</span></label>
                        <div class="phone-row">
                            <input type="text" name="phone" id="phoneInput" required class="field-input" placeholder="请输入手机号">
                            <button type="button" id="sendCodeBtn" class="code-btn" onclick="sendVerifyCode()">发送验证码</button>
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="field-label">验证码 <span class="required">*</span></label>
                        <input type="text" name="verify_code" required class="field-input" placeholder="请输入短信验证码" maxlength="6" style="max-width:200px;">
                    </div>
                </div>

                <!-- 提交 -->
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddUserDrawer()">取消</button>
                    <button type="submit" id="addUserSubmitBtn" class="btn-submit">
                        新增
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
<script>
function showAddUserModal() {
    document.getElementById('addDrawerOverlay').classList.add('show');
    document.getElementById('addUserDrawer').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAddUserDrawer(event) {
    const overlay = document.getElementById('addDrawerOverlay');
    const drawer = document.getElementById('addUserDrawer');
    if (event && event.target !== overlay) return;
    drawer.classList.remove('show');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
    const form = document.getElementById('addUserForm');
    if (form) form.reset();
    document.getElementById('avatarPreview').src = '../images/avatar-placeholder.svg';
    document.getElementById('avatarUrl').value = '';
    const codeBtn = document.getElementById('sendCodeBtn');
    codeBtn.textContent = '发送验证码';
    codeBtn.disabled = false;
    codeBtn.style.background = '';
    codeBtn.style.color = '';
    codeBtn.style.borderColor = '';
    // 重置密码错误状态
    const pwdLabel = document.getElementById('confirmPwdLabel');
    const pwdInput = document.getElementById('confirmPwdInput');
    const pwdError = document.getElementById('pwdErrorMsg');
    if (pwdLabel) pwdLabel.classList.remove('error');
    if (pwdInput) pwdInput.classList.remove('error');
    if (pwdError) pwdError.classList.remove('show');
    // 恢复密码框类型
    const pwd1 = document.getElementById('passwordInput');
    const pwd2 = document.getElementById('confirmPwdInput');
    if (pwd1) pwd1.type = 'password';
    if (pwd2) pwd2.type = 'password';
    // 恢复眼睛图标
    document.querySelectorAll('.pwd-toggle').forEach(function(btn) {
        var open = btn.querySelector('.eye-open');
        var closed = btn.querySelector('.eye-closed');
        if (open) open.style.display = '';
        if (closed) closed.style.display = 'none';
    });
}

function uploadAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        alert('图片不能超过 5MB');
        return;
    }
    const preview = document.getElementById('avatarPreview');
    const formData = new FormData();
    formData.append('avatar_file', file);
    preview.style.opacity = '0.4';
    fetch('../../upload_avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.code === 200 && res.data && res.data.url) {
            preview.src = res.data.url;
            document.getElementById('avatarUrl').value = res.data.url;
        } else {
            alert('上传失败：' + (res.message || '未知错误'));
            preview.src = '../images/avatar-placeholder.svg';
        }
        preview.style.opacity = '1';
    })
    .catch(function(e) {
        alert('网络错误：' + e.message);
        preview.src = '../images/avatar-placeholder.svg';
        preview.style.opacity = '1';
    });
}

// 密码可见性切换（按住显示，松开隐藏）
function togglePwdVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input || !btn) return;
    const eyeOpen = btn.querySelector('.eye-open');
    const eyeClosed = btn.querySelector('.eye-closed');
    if (input.type === 'password') {
        input.type = 'text';
        if (eyeOpen) eyeOpen.style.display = 'none';
        if (eyeClosed) eyeClosed.style.display = '';
    } else {
        input.type = 'password';
        if (eyeOpen) eyeOpen.style.display = '';
        if (eyeClosed) eyeClosed.style.display = 'none';
    }
}

// 实时校验密码一致性
function checkPwdMatch() {
    const pwd = document.getElementById('passwordInput');
    const confirmPwd = document.getElementById('confirmPwdInput');
    const label = document.getElementById('confirmPwdLabel');
    const input = document.getElementById('confirmPwdInput');
    const errorMsg = document.getElementById('pwdErrorMsg');
    if (!pwd || !confirmPwd || !label || !input || !errorMsg) return;
    
    if (!confirmPwd.value) {
        label.classList.remove('error');
        input.classList.remove('error');
        errorMsg.classList.remove('show');
        return;
    }
    
    if (pwd.value !== confirmPwd.value) {
        label.classList.add('error');
        input.classList.add('error');
        errorMsg.classList.add('show');
    } else {
        label.classList.remove('error');
        input.classList.remove('error');
        errorMsg.classList.remove('show');
    }
}

function sendVerifyCode() {
    const phone = document.getElementById('phoneInput').value.trim();
    if (!phone) { alert('请先输入手机号'); return; }
    if (!/^1\d{10}$/.test(phone)) { alert('请输入正确的手机号'); return; }
    const btn = document.getElementById('sendCodeBtn');
    btn.disabled = true;
    btn.textContent = '发送中...';
    fetch('member_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=sendCode&phone=' + encodeURIComponent(phone)
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.code === 0) {
            if (res.code_value) alert('验证码：' + res.code_value);
            var countdown = 60;
            var timer = setInterval(function() {
                countdown--;
                btn.textContent = countdown + 's 后重发';
                if (countdown <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = '重新发送';
                }
            }, 1000);
        } else {
            alert('发送失败：' + (res.message || '未知错误'));
            btn.disabled = false;
            btn.textContent = '重新发送';
        }
    })
    .catch(function(e) {
        alert('网络错误：' + e.message);
        btn.disabled = false;
        btn.textContent = '重新发送';
    });
}

function submitAddUser(event) {
    event.preventDefault();
    const form = document.getElementById('addUserForm');
    const password = form.querySelector('[name="password"]').value;
    const confirmPwd = form.querySelector('[name="confirm_password"]').value;
    if (password !== confirmPwd) {
        alert('两次输入的密码不一致');
        return false;
    }
    const btn = document.getElementById('addUserSubmitBtn');
    const formData = new FormData(form);
    formData.append('action', 'add');
    btn.textContent = '新增中...';
    btn.disabled = true;
    fetch('member_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.code === 0) {
            alert('新增用户成功');
            closeAddUserDrawer();
            location.reload();
        } else {
            alert('新增失败：' + (res.message || '未知错误'));
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> 新增';
            btn.disabled = false;
        }
    })
    .catch(function(e) {
        alert('网络错误：' + e.message);
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> 新增';
        btn.disabled = false;
    });
    return false;
}
</script>
