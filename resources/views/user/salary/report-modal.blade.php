<div class="modal modal-blur fade" id="reportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('user.salary.report') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">上报薪资数据</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">岗位名称 <span class="text-danger">*</span></label>
                        <input type="text" name="job_title" class="form-control" required value="{{ old('job_title', $jobTitle ?? '') }}">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">薪资下限 <span class="text-danger">*</span></label>
                            <input type="number" name="salary_min" class="form-control" required min="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">薪资上限 <span class="text-danger">*</span></label>
                            <input type="number" name="salary_max" class="form-control" required min="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">公司</label>
                            <input type="text" name="company" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">城市</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">行业</label>
                            <input type="text" name="industry" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">经验级别</label>
                            <select name="experience_level" class="form-select">
                                <option value="">请选择</option>
                                <option value="junior">初级 0-3年</option>
                                <option value="mid">中级 3-5年</option>
                                <option value="senior">高级 5-10年</option>
                                <option value="expert">专家 10年+</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link btn-link-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">提交</button>
                </div>
            </form>
        </div>
    </div>
</div>
