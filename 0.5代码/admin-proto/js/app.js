var App = {
  curGroup:'dash',curTab:'overview',
  collapsedGroups:{}, // V2.5 collapse state
  routeLabels:{}, // populated by renderSidebar

  init:function(){
    this.renderSidebar();
    this.nav('dash','overview');
    var s=this;
    var sn=document.getElementById('sidebarNav');
    /* Single delegation — nav-item */
    sn.addEventListener('click',function(e){
      var ni=e.target.closest('.nav-item');if(!ni)return;
      s.nav(ni.dataset.group,ni.dataset.tab);
    });
    /* Single delegation — section-header: expand/collapse */
    sn.addEventListener('click',function(e){
      var h=e.target.closest('.nav-section-header');if(!h)return;
      var g=h.dataset.group;
      if(s.collapsedGroups[g]){delete s.collapsedGroups[g];}else{s.collapsedGroups[g]=true;}
      s.renderSidebar();
    });
    document.getElementById('actionLogout')&&document.getElementById('actionLogout').addEventListener('click',function(){window.location.href='index.html';});
    var mo=document.getElementById('modalOverlay');if(mo)mo.addEventListener('click',function(e){if(e.target===this)s.closeModal();});
  },

  /* ── Utils ── */
  toast:function(m,t){t=t||'success';var e=document.createElement('div');e.className='toast '+t;e.textContent=m;document.body.appendChild(e);setTimeout(function(){e.style.opacity='0';e.style.transition='opacity .3s';},2000);setTimeout(function(){e.remove();},2500);},
  openModal:function(t,b,f,w){document.getElementById('modalTitle').textContent=t;document.getElementById('modalBody').innerHTML=b;document.getElementById('modalFooter').innerHTML=f||'';var m=document.getElementById('modalOverlay').querySelector('.modal');m.className='modal'+(w?' wide':'');document.getElementById('modalOverlay').classList.add('show');},
  closeModal:function(){document.getElementById('modalOverlay').classList.remove('show');},
  tc:function(s){var m={active:'green',completed:'green',pending:'amber',rejected:'red',approved:'blue',draft:'default',open:'blue',locked:'amber',settled:'green',void:'default',closing:'amber',settlement:'amber',high:'red',medium:'amber',low:'green',critical:'red',review:'amber',cooling:'amber',suspended:'red',restricted:'red',analyzing:'amber',escalated:'red',resolved:'green',pending_approval:'amber',changes_requested:'amber',executed:'green',waiting_user:'amber',in_progress:'blue',not_started:'default',needs_info:'amber',disputed:'red',partial:'amber',cancelled:'default',held:'amber',pending_claim:'amber',claimed:'green',expired_returned:'default',failed:'red',running:'blue',scheduled:'amber',pending_confirmation:'amber',normal:'green',elevated:'amber',matched:'green','in':'green',out:'red'};return m[s]||'default';},
  tag:function(s,l){var c=this.tc(s);return'<span class="tag tag-'+c+'">'+(l||s.replace(/_/g,' '))+'</span>';},
  btn:function(l,a){return'<button class="btn btn-xs'+(a?' '+a:'')+'" onclick="'+l+'">';},
  tbl:function(h,rows){if(!Array.isArray(rows))throw new TypeError('tbl rows must be an array');return'<div class="table-wrap"><table><thead><tr>'+h.map(function(c){return'<th>'+c+'</th>';}).join('')+'</tr></thead><tbody>'+rows.join('')+'</tbody></table></div>';},
  filter:function(els){return'<div class="card"><div class="filter-bar">'+els.join('')+'<button class="btn btn-primary">搜索</button></div>';},
  banner:function(t,m){return'<div class="banner banner-'+t+'">'+m+'</div>';},
  empty:function(m){return'<div class="empty"><div class="empty-icon">📭</div><p>'+(m||'暂无数据')+'</p></div>';},
  noperm:function(){return'<div class="empty"><div class="empty-icon">🔒</div><p>无权限访问 — 需特定角色授权</p></div>';},

  /* ── Interactive Modals (replacing toast stubs) ── */
  _dm:function(kv,pairs){var s=this;return'<div class="detail-grid mb-16">'+pairs.map(function(x){return'<div class="detail-item"><div class="dl">'+x[0]+'</div><div class="dv">'+x[1]+'</div></div>';}).join('')+'</div>';},
  _form:function(label,inner){return'<div class="form-group"><label>'+label+'</label>'+inner+'</div>';},

  /* KYC Decision Modal */
  openKycDecision:function(cid,action){
    var c=MOCK.kycQueue.find(function(x){return x.case_id===cid;}),s=this;
    if(!c)return;
    var labels={approve:'KYC 审批通过',reject:'KYC 审批驳回',needs_info:'要求补件'};
    var btnClass=action==='approve'?'btn-success':action==='reject'?'btn-danger':'btn-warn';
    s.openModal(labels[action]+' — '+c.user_name,
      s._dm(null,[['Case ID',cid],['用户',c.user_name+' ('+c.user_id+')'],['KYC Level',c.kyc_level],['风险',s.tag(c.risk_score)]])+
      s._form('Reason Code','<select><option>–选择原因–</option><option>document_verified</option><option>document_forged</option><option>document_expired</option><option>selfie_mismatch</option><option>suspicious_activity</option><option>insufficient_evidence</option></select>')+
      s._form('内部备注','<textarea rows="2" placeholder="Decision notes (internal only)"></textarea>')+
      (action==='needs_info'?s._form('要求补件内容','<textarea rows="2" placeholder="具体列出需要补充的材料"></textarea>'):'')+
      '<div class="bar-warn"><span>⚠</span> 此决定将写入审计日志且不可覆盖。Decision version: '+(new Date()).toISOString().slice(0,10)+'-001</div>',
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn '+btnClass+'" onclick="App.closeModal();App.toast(\''+labels[action]+'\')">确认</button>',true);
  },

  /* Risk Case Decision */
  openRiskDecision:function(cid,action){
    var r=MOCK.riskCases.find(function(x){return x.case_id===cid;}),s=this;
    if(!r)return;
    var labels={recommend_approve:'推荐通过',recommend_reject:'推荐驳回',approve:'审批通过',reject:'审批驳回',escalate:'升级处置'};
    var icon=action==='recommend_approve'||action==='approve'?'✅':action==='escalate'?'🚨':'❌';
    s.openModal(icon+' '+labels[action]+' — '+cid,
      s._dm(null,[['Case ID',cid],['类型',r.type.replace(/_/g,' ')],['对象',r.subject],['严重度',s.tag(r.severity)],['分析师',r.analyst]])+
      s._form('结论理由','<textarea rows="3" placeholder="必须填写处置理由和证据引用"></textarea>')+
      (action==='reject'||action==='recommend_reject'?s._form('建议替代方案','<textarea rows="2" placeholder="驳回后的替代处理建议"></textarea>'):'')+
      (action==='escalate'?s._form('升级级别','<select><option>Level 1 – Team Lead</option><option>Level 2 – Risk Director</option><option>Level 3 – C-Level</option></select>'+s._form('紧急程度','<select><option>Critical (SLA 1h)</option><option>High (SLA 4h)</option></select>')):''),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-danger" onclick="App.closeModal();App.toast(\''+labels[action]+'\')">确认</button>',true);
  },

  /* OTC Decision Modal */
  openOtcDecision:function(oid,action){
    var o=MOCK.otcOrders.find(function(x){return x.order_id===oid;})||MOCK.otcOrders[0],s=this;
    if(!o)return;
    var act=action||'approve';
    var labels={approve:'审批通过',reject:'审批驳回',needs_info:'要求补充信息'};
    var cls=act==='approve'?'btn-success':act==='reject'?'btn-danger':'btn-warn';
    s.openModal('OTC '+labels[act]+' — '+oid,
      s._dm(null,[['Order ID',oid],['用户',o.user],['方向',o.side==='buy'?'Buy':'Sell'],['数量',o.qty_apt+' APT'],['状态',s.tag(o.status)],['风险',s.tag(o.risk)]])+
      s._form('决定原因','<select><option>–选择–</option><option>risk_acceptable</option><option>abnormal_pattern</option>'+(act!=='approve'?'<option>insufficient_liquidity</option><option>policy_violation</option>':'<option>standard_review_passed</option>')+'</select>')+
      s._form('内部备注','<textarea rows="2" placeholder="Decision notes"></textarea>')+
      (act==='needs_info'?s._form('需要的信息','<textarea rows="2" placeholder="列出需要补充的信息"></textarea>'):''),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn '+cls+'" onclick="App.closeModal();App.toast(\''+labels[act]+'\')">确认</button>',true);
  },

  /* Market Action (Publish / Pause / LockEval) */
  openMarketAction:function(mid,action){
    var m=MOCK.markets.find(function(x){return x.market_id===mid;}),s=this;
    if(!m)return;
    var labels={publish:'发布 Market',pause:'暂停 Market',lockEval:'Lock Evaluation',settlement:'提交结算审批'};
    var isDanger=action==='pause'||action==='lockEval';
    s.openModal(labels[action]+' — '+m.event,
      s._dm(null,[['Market ID',mid],['赛事',m.event],['联赛',m.league],['状态',s.tag(m.status)],['总订单',m.total_orders],['总 APT',m.total_apt]])+
      (action==='lockEval'?
        s._dm(null,[['H/D/A',''+m.home_odds+'/'+m.draw_odds+'/'+m.away_odds],
        ['锁定时间',m.lock_time],['评估结果','<span class="tag tag-green">Pass — Liquidity OK</span>'],
        ['集群检测','<span class="tag tag-green">No abnormal clusters</span>'],
        ['冻结影响','~'+Math.round(parseInt(m.total_apt.replace(/,/g,''))||0)+' APT 将被冻结']]):
        s._form('理由','<textarea rows="2" placeholder="输入操作理由"></textarea>'))+
      '<div class="bar-warn" style="margin-top:12px;">⚠ 此操作将影响所有关联订单和资金。请确认后执行。</div>',
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn '+(isDanger?'btn-danger':'btn-success')+'" onclick="App.closeModal();App.toast(\''+labels[action]+' 已执行\')">确认执行</button>',true);
  },

  /* Parameter Candidate Editor */
  openParameterEditor:function(key){
    var p=MOCK.parameters.find(function(x){return x.key===key;}),s=this;
    if(!p)return;
    s.openModal('编辑 Candidate — '+p.key,
      s._dm(null,[['Namespace',p.namespace],['Key',p.key],['Type',p.type],['Current Release',p.current_release],['Active Since',p.current_active]])+
      s._form('Candidate Value','<textarea rows="4" placeholder="输入新的候选值...">'+(p.candidate?p.candidate.changes:'')+'</textarea>')+
      s._form('变更说明','<textarea rows="2" placeholder="描述变更内容和影响范围"></textarea>')+
      s._form('Scope','<select><option>Global (全部用户)</option><option>Staging First</option><option>Gray Release 10%</option></select>')+
      '<div class="segmented mt-12"><button class="seg-btn active">保存 Candidate</button><button class="seg-btn">Simulate</button><button class="seg-btn">Submit Release</button></div>'+
      '<div class="bar-info" style="margin-top:12px;">ℹ 保存 Candidate 不生效。需 Submit → Approval → Release → Activate 才能上线。</div>',
      '<button class="btn" onclick="App.closeModal()">关闭</button><button class="btn btn-primary" onclick="App.closeModal();App.toast(\'Candidate 已保存\')">保存 Candidate</button>',true);
  },

  /* Emergency Action Form */
  openEmergencyForm:function(){
    var s=this;
    s.openModal('🚨 发起紧急操作',
      '<div class="bar-danger" style="margin-bottom:16px;">⚠ 紧急操作需双人授权。所有操作记录在审计日志且不可删除。事后补审超时必须升级。</div>'+
      s._form('操作类型','<select><option>–选择–</option><option>market_suspend</option><option>user_suspend</option><option>parameter_rollback</option><option>otc_freeze</option><option>settlement_pause</option></select>')+
      s._form('目标对象','<input placeholder="Market ID / User ID / Release ID ...">')+
      s._form('紧急理由','<textarea rows="3" placeholder="必须详细描述紧急情况、影响范围和证据"></textarea>')+
      s._form('恢复方案','<textarea rows="2" placeholder="描述事后恢复计划和责任人"></textarea>')+
      s._form('第二授权人','<select><option>–选择授权人–</option><option>risk_approver_01</option><option>security_admin</option><option>admin</option></select>'),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-danger" onclick="App.closeModal();App.toast(\'紧急操作已发起 — 等待第二人授权\',\'warning\')">确认发起</button>',true);
  },

  /* Refund / Correction Form */
  openRefundForm:function(type){
    var s=this;
    var t=type||'refund';
    var title=t==='refund'?'新建 Refund 申请':'新建 Correction 申请';
    s.openModal(title,
      s._form('关联 Market','<select><option>–选择–</option><option>MKT-006 BAY vs BVB (void)</option><option>MKT-003 FCB vs RMD (settlement)</option><option>MKT-002 LFC vs MNU (closing)</option></select>')+
      s._form('类型','<div class="segmented"><button class="seg-btn'+(t==='refund'?' active':'')+'">Refund</button><button class="seg-btn'+(t==='correction'?' active':'')+'">Correction</button></div>')+
      s._form('理由','<textarea rows="3" placeholder="必须填写理由和证据引用">'+(t==='refund'?'Event cancelled — void all orders':'Score revision: result changed')+'</textarea>')+
      (t==='correction'?s._dm(null,[['旧 Result','DRAW'],['新 Result','<strong>HOME</strong>'],['预计影响','±45,000 APT']]):'')+
      s._form('证据','<select><option>–选择证据源–</option><option>BetBurger Feed</option><option>API Feed (primary)</option><option>Manual Review</option></select>'),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-primary" onclick="App.closeModal();App.toast(\''+title+' 已创建 — 进入审批\')">提交申请</button>',true);
  },

  /* Ticket Conversation */
  openTicketConversation:function(tid){
    var t=MOCK.tickets.find(function(x){return x.ticket_id===tid;}),s=this,tc=MOCK.ticketConversations[tid];
    if(!t)return;
    var timeline=(tc&&tc.timeline)?tc.timeline.map(function(m){var cls=m.type==='user'?'msg-user':m.type==='agent'?'msg-agent':m.type==='internal'?'msg-internal':'msg-system';
      return'<div class="msg '+cls+'" style="padding:10px 0;border-bottom:1px solid var(--gray-100);"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;"><span style="font-weight:600;font-size:12px;">'+(m.actor||'System')+'</span><span style="font-size:10px;color:var(--gray-400);">'+m.time+'</span>'+(m.visible?'<span class="tag tag-'+({user:'blue',internal:'amber'}[m.visible]||'default')+'" style="margin-left:8px;">'+(m.visible==='user'?'用户可见':'内部')+'</span>':'')+'</div><div style="font-size:13px;color:var(--gray-700);">'+m.msg+'</div></div>';
    }).join(''):'<p class="muted">暂无对话记录</p>';
    s.openModal('工单对话 — '+tid,
      s._dm(null,[['工单号',tid],['用户',t.user],['类别',t.category],['优先级',s.tag(t.priority)],['状态',s.tag(t.status)],['主题',t.subject]])+
      '<div class="divider"></div><div style="max-height:300px;overflow-y:auto;">'+timeline+'</div>'+
      '<div class="msg-input" style="margin-top:12px;"><textarea placeholder="输入回复（用户可见）..." rows="2" style="width:100%;padding:8px;border:1px solid var(--gray-300);border-radius:var(--radius);font-size:13px;resize:vertical;"></textarea></div>',
      '<button class="btn" onclick="App.closeModal()">关闭</button><button class="btn btn-warn" onclick="App.closeModal();App.openInternalNote(\'ticket\',\''+tid+'\')">内部备注</button><button class="btn btn-primary" onclick="App.closeModal();App.toast(\'回复已发送\')">发送回复</button>',true);
  },

  /* Ticket Resolution */
  openTicketResolution:function(tid){
    var t=MOCK.tickets.find(function(x){return x.ticket_id===tid;}),s=this;
    if(!t)return;
    s.openModal('解决工单 — '+tid,
      s._dm(null,[['Ticket ID',tid],['用户',t.user],['主题',t.subject],['状态',s.tag(t.status)]]),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-primary" onclick="App.closeModal();App.toast(\'工单已转派\')">转派</button><button class="btn btn-warn" onclick="App.closeModal();App.nav(\'risk\',\'case\')">升级风控</button><button class="btn btn-success" onclick="App.closeModal();App.toast(\'工单已解决\')">标记已解决</button>');
  },

  /* Internal Note */
  openInternalNote:function(context,ref){
    var s=this;
    s.openModal('添加内部 Note',
      s._form('关联对象','<input readonly value="'+(context||'')+' / '+(ref||'')+'">')+
      s._form('内部备注','<textarea rows="4" placeholder="仅内部可见的备注内容..."></textarea>'),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-warn" onclick="App.closeModal();App.toast(\'内部 Note 已保存\')">保存 Note</button>');
  },

  /* Case Creation Form (Robot Pause / Clawback / Review) */
  openCaseForm:function(caseType,ref){
    var s=this;
    var titles={robot_pause:'创建暂停/复核 Case',clawback:'创建 Clawback Case',restrict:'创建限制 Case'};
    s.openModal(titles[caseType]||'创建 Case',
      s._form('关联对象','<input readonly value="'+(ref||'')+'">')+
      s._form('Case 类型','<div class="segmented"><button class="seg-btn active">Manual Review</button><button class="seg-btn">Risk Flag</button></div>')+
      s._form('理由','<textarea rows="3" placeholder="描述需要创建 Case 的原因"></textarea>')+
      '<div class="bar-info" style="margin-top:12px;">ℹ Case 创建后进入 Risk Case 队列，需 Analyst → Approver 流程。</div>',
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-primary" onclick="App.closeModal();App.toast(\'Case 已创建 — 进入审批队列\')">创建 Case</button>');
  },

  /* Job Monitor / Retry */
  openJobMonitor:function(jid,action){
    var j=MOCK.asyncJobs.find(function(x){return x.job_id===jid;}),s=this;
    if(!j)return;
    if(action==='retry'){
      s.openModal('重试任务 — '+jid,
        s._dm(null,[['Job ID',jid],['类型',j.type.replace(/_/g,' ')],['目标',j.target],['状态',s.tag(j.status)],['重试次数',j.retries]])+
        (j.status==='failed'?'<div class="bar-danger">⚠ 资金效果任务需额外确认。重试不含业务幂等保护。</div>':'')+
        s._form('重试确认','<label><input type="checkbox"> 我确认此重试不会导致重复业务效果</label>'),
        '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-warn" onclick="App.closeModal();App.toast(\'任务重试已发起\')">确认重试</button>');
    }else{
      s.openModal('任务详情 — '+jid,
        s._dm(null,[['Job ID',jid],['类型',j.type.replace(/_/g,' ')],['目标',j.target],['状态',s.tag(j.status)],['进度',j.progress||'—'],['记录数',j.records||'—'],['失败数',j.failed||0],['DLQ',''+(j.dlq?j.failed+' items':'Empty')],['重试次数',j.retries],['错误',j.error||'—']])+
        (j.dlq?'<div class="card mt-16"><div class="card-header">DLQ Items</div><table><thead><tr><th>#</th><th>Record ID</th><th>Error</th><th>Action</th></tr></thead><tbody><tr><td>1</td><td>LE-005-post</td><td>duplicate_entry</td><td><button class="btn btn-xs btn-warn" onclick="App.closeModal();App.toast(\'单条重试已发起\')">单条重试</button></td></tr><tr><td>2</td><td>LE-012-post</td><td>balance_overflow</td><td><button class="btn btn-xs btn-warn" onclick="App.closeModal();App.toast(\'单条重试已发起\')">单条重试</button></td></tr></tbody></table></div>':'')+
        (j.status==='failed'?'<div class="btn-group mt-16"><button class="btn btn-warn" onclick="App.closeModal();App.openJobMonitor(\''+jid+'\',\'retry\')">重试</button></div>':''),
        '<button class="btn" onclick="App.closeModal()">关闭</button>');
    }
  },

  /* Release Action (Pause / Rollback) */
  openReleaseAction:function(rid,action){
    var r=MOCK.releaseSnapshots.find(function(x){return x.release_id===rid;}),s=this;
    if(!r)return;
    var act=action||'pause';
    var titles={pause:'暂停 Release',rollback:'回滚 Release'};
    s.openModal(titles[act]+' — '+rid,
      s._dm(null,[['Release ID',rid],['Parameter',r.parameter],['Version',r.version],['生效时间',r.effective],['变更说明',r.diff]])+
      s._form('理由','<textarea rows="3" placeholder="请输入'+titles[act]+'理由"></textarea>')+
      (act==='rollback'?s._form('目标版本','<select><option>回滚到上一版本</option><option>回滚到指定快照</option></select>'):'')+
      '<div class="bar-warn" style="margin-top:12px;">⚠ '+(act==='pause'?'暂停后新请求使用上次 Active Release':'回滚不覆盖历史，旧 Release 仍是不可变快照')+'</div>',
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-danger" onclick="App.closeModal();App.toast(\''+(act==='pause'?'Release 已暂停':'回滚已发起')+'\')">确认'+titles[act]+'</button>');
  },

  /* Role Editor */
  openRoleEditor:function(rid){
    var r=MOCK.adminRoles.find(function(x){return x.id===rid;}),s=this;
    if(!r)return;
    s.openModal('编辑角色 — '+r.name,
      s._form('角色名称','<input value="'+r.name+'">')+
      s._form('描述','<input value="'+r.desc+'">')+
      s._form('权限范围','<div style="max-height:200px;overflow-y:auto;border:1px solid var(--gray-200);border-radius:var(--radius);padding:8px;">'+['user:read / user:write','kyc:review / kyc:approve','ledger:read / ledger:correction','robot:read / robot:case','otc:review / otc:approve','market:manage / market:publish','risk:analyze / risk:approve','param:edit / param:activate','support:handle / support:escalate'].map(function(p){return'<div style="padding:4px 0;"><label><input type="checkbox" checked> '+p+'</label></div>';}).join('')+'</div>'),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-primary" onclick="App.closeModal();App.toast(\'角色已更新\')">保存</button>');
  },

  /* Settlement Preview */
  openSettlementPreview:function(){
    var s=this;
    s.openModal('结算模拟计算 — MKT-003 FCB vs RMD',
      s._dm(null,[['赛果','HOME (confirmed)'],['模拟结算','Sandbox #2024-06-10-001'],['总订单','1,203'],['赢注订单','482'],['总派彩','285,000 APT'],['手续费','8,550 APT (3%)'],['对账状态','<span class="tag tag-green">Match — diff=0</span>']])+
      '<div class="card mt-16"><div class="card-header">派彩明细</div><table><thead><tr><th>Selection</th><th>Orders</th><th>Type</th><th>Payout</th></tr></thead><tbody><tr><td>HOME</td><td>482</td><td><span class="tag tag-green">Win</span></td><td class="ta-r highlight">285,000</td></tr><tr><td>DRAW</td><td>421</td><td><span class="tag tag-red">Loss</span></td><td class="ta-r">0</td></tr><tr><td>AWAY</td><td>300</td><td><span class="tag tag-red">Loss</span></td><td class="ta-r">0</td></tr></tbody></table></div>',
      '<button class="btn" onclick="App.closeModal()">关闭</button><button class="btn btn-success" onclick="App.closeModal();App.toast(\'已提交 Settlement 审批\')">提交审批</button>',true);
  },

  /* Correction Proposal Submit */
  openCorrectionSubmit:function(){
    var s=this;
    s.openModal('提交更正/冲正 Proposal',
      '<div class="bar-warn">⚠ 提交后进入审批流程：Finance Review → Approval → Execution。草案无资金效果。</div>'+
      s._dm(null,[['来源 Entry','LE-004 (500 APT)'],['类型','Reversal (冲正)'],['影响','Reversal entry + Repost to B-892'],['审批路径','Finance Review → Approval → Execution']])+
      s._form('最终确认','<label><input type="checkbox"> 我确认以上信息准确，提交后将不可修改草案</label>'),
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-success" onclick="App.closeModal();App.toast(\'Proposal 已提交审批\')">确认提交</button>');
  },

  /* Ledger Entry Detail */
  openLedgerDetail:function(eid){
    var e=MOCK.ledgerEntries.find(function(x){return x.id===eid;}),s=this;
    if(!e)return;
    s.openModal('Ledger Entry — '+eid,
      s._dm(null,[['Entry ID',eid],['批次',e.batch],['用户',e.user],['类型',e.type.replace(/_/g,' ')],['方向',e.dir==='in'?'收入':'支出'],['数量 (APT)',e.qty],['结余 (APT)',e.balance],['状态',s.tag(e.status)],['时间',e.time]])+
      '<div class="bar-info" style="margin-top:12px;">ℹ 流水记录为 Append-Only。如需更正请走 Reversal Proposal 路径。</div>',
      '<button class="btn" onclick="App.closeModal()">关闭</button><button class="btn btn-warn" onclick="App.closeModal();App.nav(\'asset\',\'correction\')">创建更正 Proposal</button>');
  },

  /* Execution Detail */
  openExecutionDetail:function(tid){
    var a=MOCK.approvalTasks.find(function(x){return x.task_id===tid;}),s=this;
    if(!a)return;
    s.openModal('执行详情 — '+tid,
      s._dm(null,[['Task ID',tid],['标题',a.title],['状态',s.tag(a.status)],['决定时间',a.decided||'—'],['执行时间',a.executed||'—'],['执行结果','<span class="tag tag-green">Success</span>']])+
      '<div class="card mt-16"><div class="card-header">执行日志</div><table><thead><tr><th>步骤</th><th>状态</th><th>耗时</th></tr></thead><tbody><tr><td>1. Validate Release</td><td><span class="tag tag-green">Done</span></td><td>0.2s</td></tr><tr><td>2. Apply Parameters</td><td><span class="tag tag-green">Done</span></td><td>1.5s</td></tr><tr><td>3. Reconcile</td><td><span class="tag tag-green">Done</span></td><td>0.8s</td></tr><tr><td>4. Audit Log</td><td><span class="tag tag-green">Done</span></td><td>0.1s</td></tr></tbody></table></div>',
      '<button class="btn" onclick="App.closeModal()">关闭</button>');
  },

  /* ── Helpers for V2.4.1 ── */
  pri:function(p){var cls=p==='P0'?'pri-p0':p==='P1'?'pri-p1':p==='P1_CONDITIONAL'?'pri-p1c':p==='FUTURE'?'pri-future':'';return'<span class="pri-badge '+cls+'">'+p+'</span>';},
  gap:function(m){return'<div class="banner banner-gap"><span class="gap-icon">🔒</span><strong>上游契约未冻结</strong> — '+m+' 对象/权限/API 尚未进入 05/06 正式冻结。当前功能暂不可用（FAIL_CLOSED）。<span class="pri-sm">P1_CONDITIONAL</span></div>';},
  gapLite:function(m){return'<div class="banner banner-gap" style="padding:6px 10px;font-size:12px;margin-bottom:0;"><span style="margin-right:6px;">🔒</span>'+m+' — 上游未冻结，暂不可用</div>';},

  /* ── Sidebar V2.4.1 (8 Root) ── */
  renderSidebar:function(){
    var s=this,cur=s.curGroup;
    var sec=function(n,g,items){var isOpen=!(s.collapsedGroups[g]);return'<div class="nav-section'+(cur===g?' active-root':'')+(isOpen?' expanded':' collapsed')+'"><div class="nav-section-header" data-group="'+g+'"><span class="caret">▼</span>'+n+'</div>'+items.map(function(i){return'<div class="nav-item'+(cur===i.g&&s.curTab===i.t?' active':'')+'" data-group="'+i.g+'" data-tab="'+i.t+'">'+i.l+(i.b?' <span class="badge-warn">'+i.b+'</span>':'')+'</div>';}).join('')+'</div>';};
    document.getElementById('sidebarNav').innerHTML=
      sec('01 工作台','dash',[{l:'运营总览',g:'dash',t:'overview'},{l:'今日待办',g:'dash',t:'today',b:MOCK.stats.pendingApprovals}])+
      sec('02 用户与准入','user',[{l:'用户列表',g:'user',t:'list'},{l:'KYC 审核队列',g:'user',t:'kyc',b:MOCK.stats.pendingKyc},{l:'User 360',g:'user',t:'user360'},{l:'用户限制与恢复',g:'user',t:'restrict'},{l:'用户资产调整',g:'user',t:'adjust'},{l:'代理总览',g:'user',t:'agentOverview'},{l:'代理列表',g:'user',t:'agentList'},{l:'代理详情',g:'user',t:'agentDetail'},{l:'推荐关系',g:'user',t:'referral'},{l:'客服工单中心',g:'user',t:'tickets',b:MOCK.stats.openTickets}])+
      sec('03 资产与账本','asset',[{l:'资产总览',g:'asset',t:'overview'},{l:'APT 流水',g:'asset',t:'ledger'},{l:'池子对账',g:'asset',t:'pools'},{l:'更正/冲正',g:'asset',t:'correction'},{l:'经济模型总览',g:'asset',t:'econOverview'},{l:'奖励与结算监控',g:'asset',t:'econSettlement'},{l:'经济配置入口',g:'asset',t:'econConfig'}])+
      sec('04 机器人与权益','robot',[{l:'Robot 列表',g:'robot',t:'list'},{l:'Robot 详情',g:'robot',t:'detail'},{l:'奖励与领取监控',g:'robot',t:'reward'},{l:'升级与Power Cap',g:'robot',t:'upgrade'}])+
      sec('05 OTC 与 Power','otc',[{l:'OTC 订单',g:'otc',t:'order'},{l:'订单详情/审核',g:'otc',t:'detail'},{l:'撮合/争议监控',g:'otc',t:'monitor'},{l:'Power 账户',g:'otc',t:'power'}])+
      sec('06 赛事预测','market',[{l:'赛事/竞猜列表',g:'market',t:'list'},{l:'竞猜详情',g:'market',t:'detail'},{l:'参与订单管理',g:'market',t:'orders'},{l:'结果/结算',g:'market',t:'result'},{l:'退款/更正',g:'market',t:'refund'},{l:'数据驾驶舱',g:'market',t:'dataCockpit'},{l:'足球数据管理',g:'market',t:'footballData'},{l:'市场赔率数据',g:'market',t:'marketData'},{l:'信号与数据质量',g:'market',t:'signal'}])+
      sec('07 风控/审批/参数/策略','risk',[{l:'风险事件',g:'risk',t:'case'},{l:'审批中心',g:'risk',t:'approval',b:MOCK.stats.pendingApprovals},{l:'参数定义与候选值',g:'risk',t:'paramDef'},{l:'参数发布与快照',g:'risk',t:'paramRelease'},{l:'策略矩阵',g:'risk',t:'policy'},{l:'紧急操作',g:'risk',t:'emergency'},{l:'AI运营驾驶舱',g:'risk',t:'aiCockpit'},{l:'AI运营建议',g:'risk',t:'aiSuggest'},{l:'AI市场分析',g:'risk',t:'aiMarket'},{l:'AI策略模拟',g:'risk',t:'aiSim'},{l:'AI竞猜助手',g:'risk',t:'aiPred'},{l:'AI客服风险助手',g:'risk',t:'aiSupport'},{l:'运营报表',g:'risk',t:'report'}])+
      sec('08 客服/审计/运维','support',[{l:'全量操作日志',g:'support',t:'audit'},{l:'敏感操作审计',g:'support',t:'sensitiveAudit'},{l:'异步任务/状态 (A-OPS-001)',g:'support',t:'ops'},{l:'Provider 监控 (A-DATA-002)',g:'support',t:'provider'},{l:'数据源管理 (A-DATA-002)',g:'support',t:'datasource'},{l:'RBAC 角色 (A-OPS-001)',g:'support',t:'rbac'},{l:'语言管理 (A-OPS-001)',g:'support',t:'lang'},{l:'系统配置 (A-OPS-001)',g:'support',t:'config'},{l:'APT Migration',g:'support',t:'migration'}]);
    /* Sidebar uses single Event Delegation on sidebarNav (init), not per-element listeners */
    /* Populate breadcrumb label lookup (keyed by group:tab) */
    var lbl=this.routeLabels={};
    lbl['dash:overview']='运营总览';lbl['dash:today']='今日待办';
    lbl['user:list']='用户列表';lbl['user:kyc']='KYC 审核队列';lbl['user:user360']='User 360';lbl['user:restrict']='用户限制与恢复';lbl['user:adjust']='用户资产调整';lbl['user:agentOverview']='代理总览';lbl['user:agentList']='代理列表';lbl['user:agentDetail']='代理详情';lbl['user:referral']='推荐关系';lbl['user:tickets']='客服工单中心';
    lbl['asset:overview']='资产总览';lbl['asset:ledger']='APT 流水';lbl['asset:pools']='池子对账';lbl['asset:correction']='更正/冲正';lbl['asset:econOverview']='经济模型总览';lbl['asset:econSettlement']='奖励与结算监控';lbl['asset:econConfig']='经济配置入口';
    lbl['robot:list']='Robot 列表';lbl['robot:detail']='Robot 详情';lbl['robot:reward']='奖励与领取监控';lbl['robot:upgrade']='升级与Power Cap';
    lbl['otc:order']='OTC 订单';lbl['otc:detail']='订单详情/审核';lbl['otc:monitor']='撮合/争议监控';lbl['otc:power']='Power 账户';
    lbl['market:list']='赛事/竞猜列表';lbl['market:detail']='竞猜详情';lbl['market:orders']='参与订单管理';lbl['market:result']='结果/结算';lbl['market:refund']='退款/更正';lbl['market:dataCockpit']='数据驾驶舱';lbl['market:footballData']='足球数据管理';lbl['market:marketData']='市场赔率数据';lbl['market:signal']='信号与数据质量';
    lbl['risk:case']='风险事件';lbl['risk:approval']='审批中心';lbl['risk:paramDef']='参数定义与候选值';lbl['risk:paramRelease']='参数发布与快照';lbl['risk:policy']='策略矩阵';lbl['risk:emergency']='紧急操作';lbl['risk:aiCockpit']='AI运营驾驶舱';lbl['risk:aiSuggest']='AI运营建议';lbl['risk:aiMarket']='AI市场分析';lbl['risk:aiSim']='AI策略模拟';lbl['risk:aiPred']='AI竞猜助手';lbl['risk:aiSupport']='AI客服风险助手';lbl['risk:report']='运营报表';
    lbl['support:audit']='全量操作日志';lbl['support:sensitiveAudit']='敏感操作审计';lbl['support:ops']='异步任务/状态';lbl['support:provider']='Provider 监控';lbl['support:datasource']='数据源管理';lbl['support:rbac']='RBAC 角色';lbl['support:lang']='语言管理';lbl['support:config']='系统配置';lbl['support:migration']='APT Migration';
  },

  /* ── Nav ── */
  nav:function(g,t){
    this.curGroup=g;this.curTab=t;
    delete this.collapsedGroups[g]; // auto-expand target root
    document.querySelectorAll('.page-section').forEach(function(s){s.classList.remove('active');});
    var sec=document.getElementById('sec-'+g);if(sec)sec.classList.add('active');
    document.getElementById('breadcrumb').innerHTML='Gainode Admin / <span>'+(this.routeLabels[g+':'+t]||t)+'</span>';
    var R={dash:this.rDash,user:this.rUser,asset:this.rAsset,robot:this.rRobot,otc:this.rOtc,market:this.rMarket,risk:this.rRisk,support:this.rSupport};
    if(R[g])R[g].call(this,sec,t);
    this.renderSidebar();
  },

  tabs:function(items,cur){
    var s=this;
    return'<div class="tabs">'+items.map(function(i){return'<div class="tab'+(i.id===cur?' active':'')+'" onclick="App.nav(\''+i.group+'\',\''+i.id+'\')">'+i.label+(i.badge?' <span class="count">'+i.badge+'</span>':'')+'</div>';}).join('')+'</div>';
  },

  /* ===== 01 工作台 ===== */
  rDash:function(sec,tab){
    var s=MOCK.stats,al=MOCK.recentActivities,ss=MOCK.systemStatus,self=this;
    var ts=[{id:'overview',label:'运营总览',group:'dash'},{id:'today',label:'今日待办',group:'dash',badge:s.pendingApprovals}];
    var body='';
    if(tab==='overview'||!tab){
      body='<div class="stat-grid">'+
        ['总注册用户|'+s.totalUsers.toLocaleString()+'|↑ 12% vs 上月|up','今日活跃|'+s.todayActive.toLocaleString()+'|在线率 13.5%|up','APT 流通量|'+s.aptInCirculation+'|总铸造 15.2M|up','法定收入累计|'+s.totalRevenueFiat+'|稳定增长|up','待审核 KYC|'+s.pendingKyc+'|⚠ 需要处理|warn','审批中心待办|'+s.pendingApprovals+'|需要审批|warn','活跃 Robot|'+s.robotActiveCount.toLocaleString()+'|运行中|up','活跃 Market|'+s.marketCount+'||'].map(function(x){var p=x.split('|');return'<div class="stat-card"><div class="s-label">'+p[0]+'</div><div class="s-value"'+(p[3]==='warn'?' style="color:var(--warning-600)"':'')+'>'+p[1]+'</div><div class="s-trend '+p[3]+'">'+p[2]+'</div></div>';}).join('')+
      '</div>'+
      '<div class="section-grid">'+
        '<div class="card"><div class="card-header">Provider 健康</div>'+
          '<p class="muted" style="padding:16px;">Provider 合同未签署（CONTRACT_GAP），Runtime 监控数据不可用。详情见 08 运维模块。</p>'+
        '</div>'+
        '<div class="card"><div class="card-header">AI / 数据健康</div>'+
          '<p class="muted" style="padding:16px;">AI Pipeline 对象未在 05 中冻结。AI 不直接执行高风险动作（HUMAN_IN_LOOP）。</p>'+
        '</div>'+
      '</div>'+
      '<div class="section-grid">'+
        '<div class="card"><div class="card-header">近期活动</div>'+al.map(function(a){return'<div class="flex-row" style="padding:6px 0;border-bottom:1px solid var(--gray-100);"><span style="width:60px;font-size:11px;color:var(--gray-400);">'+a.time+'</span><span style="color:var(--gray-700);">'+a.text+'</span><span class="muted" style="margin-left:auto;">'+a.detail+'</span></div>';}).join('')+'</div>'+
        '<div class="card"><div class="card-header">系统状态</div>'+Object.keys(ss).map(function(k){return'<div class="flex-row" style="padding:6px 0;border-bottom:1px solid var(--gray-100);"><span style="color:var(--gray-500);width:120px;">'+k+'</span><span class="tag '+(ss[k]==='Normal'||ss[k]==='Running'?'tag-green':'tag-amber')+'">'+ss[k]+'</span></div>';}).join('')+'</div>'+
      '</div>'+
      '<div class="card" style="margin-top:16px;"><div class="card-header">快捷入口</div><div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;">'+
        ['👤 用户列表|user|list','🆔 KYC 审核|user|kyc','🛡️ 风险事件|risk|case','✅ 审批中心|risk|approval','🤖 Robot 管理|robot|list','💱 OTC 订单|otc|order','⚽ 赛事预测|market|list','🎫 工单中心|user|tickets'].map(function(x){var p=x.split('|');return'<button class="btn" onclick="App.nav(\''+p[1]+'\',\''+p[2]+'\')">'+p[0]+'</button>';}).join('')+'</div></div>';
    }else{
      body=self.filter(['<select><option>全部类型</option><option>审批</option><option>KYC</option><option>风险</option></select><select><option>全部优先级</option></select>'])+
        self.tbl(['类型','标题','优先级','请求人','SLA','操作'],
            MOCK.approvalTasks.filter(function(t){return t.status==='pending';}).map(function(t){return'<tr><td>'+self.tag(t.type.replace(/_/g,' '))+'</td><td>'+t.title+'</td><td>'+self.tag(t.risk)+'</td><td>'+t.requester+'</td><td>'+t.sla+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.nav(\'risk\',\'approval\')">处理</button></div></td></tr>';}).concat(
            MOCK.kycQueue.filter(function(k){return k.status==='pending';}).map(function(k){return'<tr><td>'+self.tag('kyc')+'</td><td>'+k.user_name+' KYC 审核</td><td>'+self.tag('medium')+'</td><td>System</td><td>24h</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.nav(\'user\',\'kyc\')">审核</button></div></td></tr>';})));
    }
    sec.innerHTML='<div class="page-header"><h2>工作台</h2><span class="meta">最后更新：实时</span></div>'+self.tabs(ts,tab)+body;
  },

  /* ===== 02 用户与准入 ===== */
  rUser:function(sec,tab){
    var s=this,ts=[
      {id:'list',label:'用户列表',group:'user',p:'P0'},
      {id:'kyc',label:'KYC 审核队列',group:'user',badge:MOCK.stats.pendingKyc,p:'P0'},
      {id:'user360',label:'User 360',group:'user',p:'P0'},
      {id:'restrict',label:'用户限制与恢复',group:'user',p:'P0'},
      {id:'adjust',label:'用户资产调整',group:'user',p:'P0'},
      {id:'agentOverview',label:'代理总览',group:'user',p:'P1_CONDITIONAL'},
      {id:'agentList',label:'代理列表',group:'user',p:'P1_CONDITIONAL'},
      {id:'agentDetail',label:'代理详情',group:'user',p:'P1_CONDITIONAL'},
      {id:'referral',label:'推荐关系',group:'user',p:'P1_CONDITIONAL'},
      {id:'tickets',label:'客服工单',group:'user',badge:MOCK.stats.openTickets,p:'P0'}
    ];
    var body='';
    if(tab==='list'){
      body=s.filter(['<input placeholder="搜索 UID / 手机号 / 邮箱 / 推荐码"><select><option>全部 KYC</option><option>approved</option><option>pending</option></select><select><option>全部状态</option><option>active</option><option>restricted</option></select><button class="btn">保存视图</button>'])+
        s.tbl(['UID','昵称','手机号','KYC','等级','状态','直推','上级','推荐码','Robot','操作'],
          MOCK.users.map(function(m){return'<tr><td class="cell-mono">'+m.id+'</td><td>'+m.display_name+'</td><td>'+m.phone+'</td><td>'+s.tag(m.kyc_status)+'</td><td>'+m.kyc_level+'</td><td>'+s.tag(m.status)+'</td><td>3</td><td>U-004</td><td class="cell-mono">REF-'+m.id+' <button class="btn btn-xs" onclick="App.toast(\'已复制\')">📋</button></td><td>Lv.'+(m.global_p_level||0)+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.nav(\'user\',\'user360\')">360</button><button class="btn btn-xs btn-warn" onclick="App.nav(\'user\',\'restrict\')">限制</button></div></td></tr>';}));
    }else if(tab==='kyc'){
      body=s.filter(['<select><option>全部状态</option><option>pending</option><option>needs_info</option><option>review</option></select>'])+
        s.tbl(['Case ID','用户','等级','状态','文件','风险','提交','操作'],
          MOCK.kycQueue.map(function(c){var bt='';
            if(c.status==='pending')bt='<button class="btn btn-xs btn-success" onclick="App.openKycDecision(\''+c.case_id+'\',\'approve\')">通过</button><button class="btn btn-xs btn-danger" onclick="App.openKycDecision(\''+c.case_id+'\',\'reject\')">驳回</button>';
            else if(c.status==='needs_info')bt='<button class="btn btn-xs btn-warn" onclick="App.openKycDecision(\''+c.case_id+'\',\'needs_info\')">补件</button>';
            else if(c.status==='review')bt='<button class="btn btn-xs btn-primary" onclick="App.openKycReview(\''+c.case_id+'\')">复核</button>';
            return'<tr><td class="cell-mono">'+c.case_id+'</td><td>'+c.user_name+'</td><td>'+c.kyc_level+'</td><td>'+s.tag(c.status)+'</td><td>'+Object.keys(c.documents||{}).map(function(k){return'<span class="tag tag-'+(c.documents[k]==='ok'?'green':'amber')+'">'+k+':'+c.documents[k]+'</span>';}).join(' ')+'</td><td>'+s.tag(c.risk_score)+'</td><td>'+c.submitted+'</td><td><div class="btn-group">'+bt+'</div></td></tr>';}));
    }else if(tab==='user360'){
      body='<div class="banner banner-info">User 360 展示用户全貌：准入/KYC/Robot/APT/Power/OTC/竞猜/风险/工单。高风险动作不在详情页直接执行。</div>'+s._u360(MOCK.user360);
    }else if(tab==='restrict'){
      body=s.banner('info','账户/余额/OTC/Robot 分别限制，不隐式推导。冻结余额和重大恢复需审批（Requester ≠ Approver）。')+
        s.filter(['<input placeholder="搜索 UID"><select><option>全部类型</option><option>冻结账户</option><option>冻结余额</option><option>冻结OTC</option><option>限制Robot</option></select>'])+
        s.tbl(['Case ID','用户','类型','原因','生效','到期','状态','操作'],
          (MOCK.userRestrictions||[]).map(function(r){return'<tr><td class="cell-mono">'+r.case_id+'</td><td>'+r.user+'</td><td>'+s.tag(r.type)+'</td><td>'+r.reason+'</td><td>'+r.effective+'</td><td>'+r.expiry+'</td><td>'+s.tag(r.status)+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.openRestrictForm(\''+r.case_id+'\')">详情</button></div></td></tr>';}));
    }else if(tab==='adjust'){
      body=s.gap('上游对象未冻结 — 资产调整依赖 Ledger Adjustment Object，未在 05 中正式冻结。当前为规划预览。')+
        '<div class="card" style="max-width:960px;">'+
          '<div class="card-header">资产调整 — 规划预览</div>'+
          '<p class="muted" style="padding:16px;">资产调整走账本流程：Proposal → Preview → Impact → Approval → Execution → Ledger。禁止直接 SET balance。Requester ≠ Approver（SoD）。</p>'+
          '<div class="stat-grid dimmed">'+['待审批调整|—','本月调整次数|—','调整总金额|—','成功率|—'].map(function(x){var p=x.split('|');return'<div class="stat-card"><div class="s-label">'+p[0]+'</div><div class="s-value">'+p[1]+'</div></div>';}).join('')+'</div>'+
          '<div class="tabs mt-16"><div class="tab active">调整历史</div></div>'+
          '<div class="table-wrap"><table><thead><tr><th>ID</th><th>用户</th><th>类型</th><th>Delta</th><th>Before/After</th><th>原因</th><th>审批人</th><th>状态</th><th>时间</th></tr></thead><tbody>'+
            '<tr><td class="cell-mono">ADJ-001</td><td>U-004/John Smith</td><td>credit</td><td class="ta-r highlight">+1,500</td><td>46,700 → 48,200</td><td>工单补偿</td><td>risk_approver_01</td><td><span class="tag tag-green">已执行</span></td><td>2024-06-05</td></tr>'+
            '<tr><td class="cell-mono">ADJ-002</td><td>U-003</td><td>debit</td><td class="ta-r" style="color:var(--danger-600);">-500</td><td>5,200 → 4,700</td><td>欺诈案件</td><td>security_admin</td><td><span class="tag tag-green">已执行</span></td><td>2024-06-03</td></tr>'+
          '</tbody></table></div>'+
        '</div>';
    }else if(tab==='agentOverview'||tab==='agentList'||tab==='agentDetail'||tab==='referral'){
      body=s.gap('代理管理（Affiliate）')+
        '<div class="card"><div class="card-header">代理管理 — 规划预览</div>'+
        '<p class="muted" style="padding:16px;">代理总览/列表/详情/推荐关系归入用户与准入模块。当前 Affiliate Object 未在 05 中正式冻结，生产实现暂不可用。</p>'+
        '<div class="stat-grid dimmed">'+['代理总数|—','有效代理|—','今日新增|—','代理域用户|—'].map(function(x){var p=x.split('|');return'<div class="stat-card"><div class="s-label">'+p[0]+'</div><div class="s-value">'+p[1]+'</div></div>';}).join('')+'</div>'+
        s.tbl(['代理ID','名称','负责人','等级','邀请码','状态','操作'],[
          '<tr><td class="cell-mono">AF-001</td><td>北美区域代理</td><td>U-010/Emma Wilson</td><td>Gold</td><td class="cell-mono">INV-N-001</td><td><span class="tag tag-default">规划中</span></td><td>—</td></tr>'
        ])+'</div>';
    }else{
      body=s.filter(['<select><option>全部优先级</option><option>critical</option><option>high</option></select><select><option>全部状态</option><option>处理中</option><option>等待用户</option></select>'])+
        s.tbl(['工单号','用户','类别','优先级','状态','主题','负责人','SLA','操作'],
          MOCK.tickets.map(function(t){return'<tr><td class="cell-mono">'+t.ticket_id+'</td><td>'+t.user+'</td><td>'+t.category+'</td><td>'+s.tag(t.priority)+'</td><td>'+s.tag(t.status)+'</td><td>'+t.subject+'</td><td>'+t.assignee+'</td><td>'+t.sla+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.openTicketConversation(\''+t.ticket_id+'\')">处理</button>'+(t.status==='in_progress'?'<button class="btn btn-xs btn-success" onclick="App.openTicketResolution(\''+t.ticket_id+'\')">解决</button>':'')+'</div></td></tr>';}));
    }
    sec.innerHTML='<div class="page-header"><h2>用户与准入</h2></div>'+s.tabs(ts,tab)+body;
  },

  /* ── User Restriction Form ── */
  openRestrictForm:function(cid){
    var s=this;
    if(cid==='new'){
      s.openModal('创建用户限制',
        s._form('用户','<select><option>选择用户</option><option>U-001/Alex Chen</option><option>U-004/John Smith</option></select>')+
        s._form('限制类型','<select><option>–选择–</option><option>freeze_account</option><option>freeze_balance</option><option>freeze_otc</option><option>restrict_robot</option></select>')+
        s._form('原因','<textarea rows="3" placeholder="输入限制原因和证据"></textarea>')+
        s._form('到期时间','<input type="datetime-local">')+
        '<div class="bar-warn mt-12">⚠ 冻结余额和重大恢复需审批。发起人 ≠ 审批人（SoD）。</div>',
        '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-danger" onclick="App.closeModal();App.toast(\'限制申请已提交 — 等待审批\')">提交审批</button>');
    }else{
      s.openModal('限制详情 — '+cid,
        s._dm(null,[['Case ID',cid],['用户','U-004/John Smith'],['类型','<span class=\'tag tag-red\'>freeze_balance</span>'],['原因','风控要求'],['生效','2024-06-01'],['到期','2024-06-15'],['状态','<span class=\'tag tag-red\'>active</span>'],['操作人','admin'],['审批人','risk_approver_01']]),
        '<button class="btn" onclick="App.closeModal()">关闭</button><button class="btn btn-warn" onclick="App.closeModal();App.toast(\'解除申请已提交\')">申请解除</button>');
    }
  },

  _u360:function(d){
    var s=this;
    return'<div class="card"><div class="card-header">Header Summary</div>'+
      '<div class="detail-grid col4 mb-16">'+['Status|'+s.tag(d.status),'KYC|'+s.tag(d.kyc.status)+' Lv.'+d.kyc.level,'Robot|Lv.'+d.robot.level+' <span class="tag tag-green">'+d.robot.status+'</span>','APT Balance|'+d.apt.balance_apt_i+' APT'].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv'+(p[0]==='APT Balance'?' highlight':'')+'">'+p[1]+'</div></div>';}).join('')+'</div>'+
      '<div class="tabs"><div class="tab active">Admission</div><div class="tab">Robot</div><div class="tab">APT</div><div class="tab">Power</div><div class="tab">OTC</div><div class="tab">Prediction</div><div class="tab">Risk <span class="count">'+d.risk.history+'</span></div><div class="tab">Support <span class="count">'+d.tickets.history+'</span></div></div>'+
      '<div class="section-grid mt-16">'+
        '<div class="card"><div class="card-header">Admission</div><div class="detail-grid col2">'+['Country|'+d.kyc.country,'Documents|'+d.kyc.documents,'MFA|'+(d.security.mfa_enabled?'Enabled':'Disabled'),'Devices|'+d.security.devices,'Last Pwd Change|'+d.security.last_password_change].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div></div>'+
        '<div class="card"><div class="card-header">APT Ledger</div><div class="detail-grid col2">'+['Balance|'+d.apt.balance_apt_i+' APT','Frozen|'+d.apt.frozen_apt_i+' APT','Total Earned|'+d.apt.total_earned+' APT'].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv'+(p[0]==='Balance'?' highlight':'')+'">'+p[1]+'</div></div>';}).join('')+'</div></div>'+
        '<div class="card"><div class="card-header">Power</div><div class="detail-grid col2">'+['Available|'+d.power.available,'Frozen|'+d.power.frozen,'Consumed|'+d.power.consumed,'Cap|'+d.power.cap].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div></div>'+
        '<div class="card"><div class="card-header">Prediction</div><div class="detail-grid col2">'+['Open Orders|'+d.prediction.open_orders,'Settled|'+d.prediction.settled,'Won|'+d.prediction.won,'Lost|'+d.prediction.lost,'Refunded|'+d.prediction.refunded].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div></div>'+
      '</div></div>';
  },

  openKycReview:function(cid){
    var c=MOCK.kycQueue.find(function(x){return x.case_id===cid;}),s=this;
    if(!c)return;
    s.openModal('KYC复核 — '+c.user_name,
      '<div class="detail-grid mb-16">'+['用户|'+c.user_name+' ('+c.user_id+')','KYC Level|'+c.kyc_level,'风险评分|'+s.tag(c.risk_score)].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div>'+
      '<div class="detail-grid col4">'+Object.keys(c.documents||{}).map(function(k){return'<div class="detail-item"><div class="dl">'+k+'</div><div class="dv">'+s.tag(c.documents[k])+'</div></div>';}).join('')+'</div>',
      '<button class="btn" onclick="App.closeModal()">取消</button><button class="btn btn-success" onclick="App.closeModal();App.toast(\'KYC 复核通过\')">通过</button><button class="btn btn-danger" onclick="App.closeModal();App.toast(\'KYC 复核驳回\')">驳回</button>');
  },

  /* ===== 03 资产与账本 ===== */
  rAsset:function(sec,tab){
    var s=this,ts=[
      {id:'overview',label:'资产总览',group:'asset',p:'P0'},
      {id:'ledger',label:'APT 流水',group:'asset',p:'P0'},
      {id:'pools',label:'池子对账',group:'asset',p:'P0'},
      {id:'correction',label:'更正/冲正',group:'asset',p:'P0'},
      {id:'econOverview',label:'经济模型总览',group:'asset',p:'P0'},
      {id:'econSettlement',label:'奖励与结算监控',group:'asset',p:'P0'},
      {id:'econConfig',label:'经济配置入口',group:'asset',p:'P0'}
    ];
    var body='',a=MOCK.assetOverview;
    if(tab==='overview'){
      body=s.banner('info','财务信息概览 — 只读视图。更正、调拨必须经过 Proposal → Approval → Execution 路径。')+
        '<div class="stat-grid">'+['APT 总铸造|'+a.totalAptMinted,'APT 流通中|'+a.aptInCirculation,'APT 冻结中|'+a.aptFrozen,'待结算|'+a.aptPendingSettlement].map(function(x){var p=x.split('|');return'<div class="stat-card"><div class="s-label">'+p[0]+'</div><div class="s-value">'+p[1]+'</div></div>';}).join('')+'</div>'+
        '<div class="section-grid">'+
          '<div class="card"><div class="card-header">OTC 结算储备</div><div class="detail-grid col2">'+['已批准额度|'+a.otcReserve.approved.toLocaleString()+' APT','已承诺/占用|'+a.otcReserve.committed.toLocaleString()+' APT','可用量|'+a.otcReserve.available.toLocaleString()+' APT','对账时间|'+a.otcReserve.reconciled].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv'+(p[0]==='可用量'?' highlight':'')+'">'+p[1]+'</div></div>';}).join('')+'</div></div>'+
          '<div class="card"><div class="card-header">运营预算</div><div class="detail-grid col2">'+['已批准额度|'+a.opsBudget.approved.toLocaleString()+' APT','已支出|'+a.opsBudget.spent.toLocaleString()+' APT','剩余|'+a.opsBudget.remaining.toLocaleString()+' APT','对账时间|'+a.opsBudget.reconciled].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv'+(p[0]==='剩余'?' highlight':'')+'">'+p[1]+'</div></div>';}).join('')+'</div></div>'+
        '</div>';
    }else if(tab==='ledger'){
      body=s.banner('warn','流体为 Append-Only，不支持内联编辑余额/流水。冲正必须走 Reversal Proposal。')+
        s.filter(['<input placeholder="搜索用户"><select><option>全部类型</option></select>'])+
        s.tbl(['Entry ID','批次','用户','类型','方向','数量','余额','状态','时间','操作'],
          MOCK.ledgerEntries.map(function(e){return'<tr><td class="cell-mono">'+e.id+'</td><td class="cell-mono">'+e.batch+'</td><td>'+e.user+'</td><td>'+e.type+'</td><td>'+(e.dir==='in'?'<span class="tag tag-green">收入</span>':'<span class="tag tag-red">支出</span>')+'</td><td class="ta-r">'+e.qty+'</td><td class="ta-r">'+e.balance+'</td><td>'+s.tag(e.status)+'</td><td>'+e.time+'</td><td><div class="btn-group"><button class="btn btn-xs" onclick="App.openLedgerDetail(\''+e.id+'\')">查看</button><button class="btn btn-xs btn-warn" onclick="App.nav(\'asset\',\'correction\')">标异常</button></div></td></tr>';}));
    }else if(tab==='pools'){
      body=s.banner('info','四大账户隔离：OTC 储备 ≠ 运营预算 ≠ AI Reward Pool ≠ 竞猜资金。')+
        '<div class="card">'+s.tbl(['池子名称','余额','预算','本月支出','对账','最后对账'],
          MOCK.pools.map(function(p){return'<tr><td><strong>'+p.name+'</strong></td><td class="ta-r highlight">'+(p.balance||p.available||'—').toLocaleString()+' APT</td><td class="ta-r">'+(p.budgeted||p.approved||p.spent||'—').toLocaleString()+' APT</td><td class="ta-r">'+(p.spent_this_month||'—')+'</td><td>'+(p.reconciled?'<span class="tag tag-green">已对账</span>':'<span class="tag tag-amber">未对账</span>')+'</td><td>'+p.last_recon+'</td></tr>';}))+'</div>';
    }else if(tab==='correction'){
      body=s.banner('info','这里不是"修账按钮"——创建受控的 ledger correction proposal，不直接改账。草案无资金效果，必须经过审批。')+
        '<div class="card" style="max-width:960px;">'+
          '<div class="card-header">新建更正/冲正申请</div>'+
          '<div class="form-group"><label>来源 Entry</label><select><option>选择需要更正/冲正的流水记录</option><option>LE-004 — posting error</option></select></div>'+
          '<div class="form-group"><label>类型</label><div class="segmented"><button class="seg-btn active">Reversal (冲正)</button><button class="seg-btn">Correction (更正)</button></div></div>'+
          '<div class="form-group"><label>理由</label><textarea rows="3" placeholder="必须填写更正理由，包括证据引用"></textarea></div>'+
          '<div class="form-group"><label>影响预览</label><div class="detail-item"><div class="dl">受影响金额</div><div class="dv highlight">LE-004: 500 APT</div></div></div>'+
          '<div class="form-group"><label>审批路径</label><div class="approval-flow"><div class="approval-step done"><div class="step-dot">✓</div><div class="step-label">草案</div></div><div class="approval-line done"></div><div class="approval-step"><div class="step-dot">○</div><div class="step-label">财务复核</div></div><div class="approval-line"></div><div class="approval-step"><div class="step-dot">○</div><div class="step-label">审批</div></div><div class="approval-line"></div><div class="approval-step"><div class="step-dot">○</div><div class="step-label">执行</div></div></div></div>'+
          '<div class="btn-group mt-16"><button class="btn btn-primary" onclick="App.openCorrectionSubmit()">保存草案</button><button class="btn btn-success" onclick="App.openCorrectionSubmit()">提交审批（SoD：发起人≠审批人）</button></div>'+
        '</div>';
    }else if(tab==='econOverview'){
      body=s.banner('info','经济模型运行总览 — 监控 Reward/Power/Settlement 全局运行。AI 和人工建议不走经济模型参数直接修改。')+
        '<div class="stat-grid">'+['今日派发 Reward|1,234 APT','本月累计|45,600 APT','活跃领取用户|8,934','Power 总消耗|180,500','竞猜结算额|285,000 APT','对账差异|0'].map(function(x){var p=x.split('|');return'<div class="stat-card"><div class="s-label">'+p[0]+'</div><div class="s-value">'+p[1]+'</div></div>';}).join('')+'</div>'+
        '<div class="card mt-16">'+s.tbl(['指标','当前值','昨日','趋势','阈值'],[
          '<tr><td>每日 Reward 总额</td><td class="highlight">1,234 APT</td><td>1,180</td><td>↑ 4.5%</td><td>正常</td></tr>',
          '<tr><td>平均 Reward/用户</td><td class="highlight">0.14 APT</td><td>0.13</td><td>↑ 7%</td><td>正常</td></tr>',
          '<tr><td>Power 消耗率</td><td class="highlight">12.3%</td><td>11.8%</td><td>↑</td><td>正常</td></tr>',
          '<tr><td>结算金额</td><td class="highlight">285,000</td><td>310,000</td><td>↓ 8%</td><td>正常</td></tr>',
          '<tr><td>手续费收入</td><td class="highlight">8,550</td><td>9,300</td><td>↓ 8%</td><td>正常</td></tr>'
        ])+'</div>';
    }else if(tab==='econSettlement'){
      body=s.banner('info','奖励与结算执行监控 — 查看 Reward batch 和 Settlement batch 的执行状态。')+
        '<div class="card">'+s.tbl(['批次','类型','周期','记录数','成功','失败','状态','执行时间'],
          MOCK.settlementBatches.concat([{batch_id:'B-892',type:'reward',period:'2024-06-10',records:'1,234',success:'1,232',failed:'2',status:'completed',executed:'2024-06-10 06:30'}]).map(function(b){return'<tr><td class="cell-mono">'+(b.batch_id||b.journal_batch)+'</td><td>'+(b.type||'settlement')+'</td><td>'+b.period+'</td><td class="ta-r">'+(b.records||b.orders_total)+'</td><td class="ta-r">'+(b.success||b.win_orders||'—')+'</td><td class="ta-r">'+(b.failed||'0')+'</td><td>'+s.tag(b.status)+'</td><td>'+(b.executed||b.settled||'—')+'</td></tr>';}))+'</div>';
    }else{
      body=s.banner('info','经济模型配置入口 — 统一跳转到参数中心（唯一真源）。不存在第二套 Active Parameter。')+
        '<div class="card"><div class="card-header">经济模型相关参数</div>'+
          '<p class="muted" style="padding:12px 16px 0;">以下配置项均在 <strong>参数中心</strong> 中管理。此处为业务视角入口，所有编辑跳转到 Parameter Center。</p>'+
          s.tbl(['参数 Key','业务域','当前 Release','活跃值','操作'],
            MOCK.parameters.filter(function(p){return ['AI','Prediction','OTC','Risk'].indexOf(p.namespace)>=0;}).map(function(p){return'<tr><td class="cell-mono">'+p.key+'</td><td>'+p.namespace+'</td><td class="cell-mono">'+p.current_release+'</td><td>'+(p.value||'TABLE')+'</td><td><button class="btn btn-xs btn-primary" onclick="App.nav(\'risk\',\'paramDef\')">去参数中心</button></td></tr>';}))+
        '</div>';
    }
    sec.innerHTML='<div class="page-header"><h2>资产与账本</h2></div>'+s.tabs(ts,tab)+body;
  },

  /* ===== 04 机器人与权益 ===== */
  rRobot:function(sec,tab){
    var s=this,ts=[{id:'list',label:'Robot 列表',group:'robot',p:'P0'},{id:'detail',label:'Robot 详情',group:'robot',p:'P0'},{id:'reward',label:'奖励与领取监控',group:'robot',p:'P0'},{id:'upgrade',label:'升级与Power Cap',group:'robot',p:'P1'}];
    var body='';
    if(tab==='list'){
      body=s.filter(['<input placeholder="搜索 User / Robot ID"><select><option>全部等级组</option></select><select><option>全部状态</option><option>active</option><option>review</option></select>'])+
        s.tbl(['Robot ID','用户','等级','组别','状态','容量','系数','Power Cap','规则版本','操作'],
          MOCK.robots.map(function(r){return'<tr><td class="cell-mono">'+r.robot_id+'</td><td>'+r.user+'</td><td><span class="tag tag-gold">Lv.'+r.level+'</span></td><td>'+r.level_group+'</td><td>'+s.tag(r.status)+'</td><td class="ta-r">'+r.standard_capacity+'</td><td class="ta-r">'+r.daily_reward_coefficient+'</td><td class="ta-r">'+r.power_cap+'</td><td class="cell-mono">'+r.rule_version+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.nav(\'robot\',\'detail\')">详情</button></div></td></tr>';}));
    }else if(tab==='detail'){
      body=s.banner('info','Robot 详情是事实页。参数改动去 Parameter Center，高风险动作创建 case/proposal 再审批。')+s._robotDetail(MOCK.robotDetail);
    }else if(tab==='reward'){
      body=s.filter(['<select><option>全部状态</option><option>held</option><option>待领取</option><option>已领取</option></select><input placeholder="批次">'])+
        s.tbl(['Reward ID','用户','Robot','容量','系数','待领取 APT','状态','批次','周期','操作'],
          MOCK.rewards.map(function(r){return'<tr><td class="cell-mono">'+r.reward_id+'</td><td>'+r.user+'</td><td>'+r.robot+'</td><td class="ta-r">'+r.std_capacity+'</td><td class="ta-r">'+r.coeff+'</td><td class="ta-r highlight">'+r.pending_apt+'</td><td>'+s.tag(r.status)+'</td><td class="cell-mono">'+r.batch+'</td><td>'+r.period+'</td><td><div class="btn-group"><button class="btn btn-xs" onclick="App.nav(\'robot\',\'detail\')">查看</button><button class="btn btn-xs btn-danger" onclick="App.openCaseForm(\'clawback\',\''+r.reward_id+'\')">回退</button></div></td></tr>';}));
    }else{
      body=s.banner('info','升级与 Power Cap 变化 — 查看机器人升级历史和 Power Cap 变化趋势。参数改动在参数中心。')+
        s.tbl(['日期','Robot ID','用户','从','到','升级花费 APT','Power Cap 变化','规则版本'],
          (MOCK.robotDetail.upgrades||[]).map(function(u){return'<tr><td>'+u.date+'</td><td class="cell-mono">RB-002</td><td>U-004</td><td>Lv.'+u.from+'</td><td>Lv.'+u.to+'</td><td class="ta-r">'+u.cost_apt+'</td><td class="ta-r">'+u.power_cap_after+'</td><td class="cell-mono">V3.2.1</td></tr>';}))+
        '<p class="muted" style="padding:8px 0;">该页面为 P1，Robot 详情 Tab 内已包含升级历史。</p>';
    }
    sec.innerHTML='<div class="page-header"><h2>机器人与权益</h2></div>'+s.tabs(ts,tab)+body;
  },

  _robotDetail:function(d){
    var s=this;
    return'<div class="card"><div class="card-header">Object Header</div>'+
      '<div class="detail-grid col4 mb-16">'+['Robot ID|'+d.robot_id,'User|'+d.user,'Level|<span class="tag tag-gold">Lv.'+d.level+'</span>','Status|'+s.tag(d.status),'Capacity|'+d.standard_capacity,'Power Cap|'+d.power_cap,'Coeff|'+d.daily_reward_coefficient,'Rule Ver|'+d.rule_version].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div>'+
      '<div class="tabs"><div class="tab active">Upgrades</div><div class="tab">Rewards</div><div class="tab">Power Ledger</div></div>'+
      '<div class="section-grid mt-16">'+
        '<div class="card"><div class="card-header">升级历史</div>'+s.tbl(['日期','From','To','Cost APT','Power Cap After'],d.upgrades.map(function(u){return'<tr><td>'+u.date+'</td><td>Lv.'+u.from+'</td><td>Lv.'+u.to+'</td><td class="ta-r">'+u.cost_apt+'</td><td class="ta-r">'+u.power_cap_after+'</td></tr>';}))+'</div>'+
        '<div class="card"><div class="card-header">Reward 历史</div>'+s.tbl(['周期','APT','状态'],d.rewardHistory.map(function(r){return'<tr><td>'+r.period+'</td><td class="ta-r highlight">'+r.apt+'</td><td>'+s.tag(r.status)+'</td></tr>';}))+'</div>'+
        '<div class="card"><div class="card-header">Power Ledger</div>'+s.tbl(['日期','动作','数量','余额'],d.powerLedger.map(function(p){return'<tr><td>'+p.date+'</td><td>'+p.action+'</td><td class="ta-r">'+p.qty+'</td><td class="ta-r">'+p.balance+'</td></tr>';}))+'</div>'+
      '</div>'+
      '<div class="btn-group mt-16"><button class="btn btn-warn" onclick="App.openCaseForm(\'robot_pause\',\'RB-002\')">创建暂停 Case</button><button class="btn" onclick="App.nav(\'risk\',\'paramDef\')">去参数中心</button><button class="btn" onclick="App.nav(\'user\',\'user360\')">去 User 360</button></div></div>';
  },

  /* ===== 05 OTC 与 Power ===== */
  rOtc:function(sec,tab){
    var s=this,ts=[{id:'order',label:'OTC 订单',group:'otc',p:'P0'},{id:'detail',label:'订单详情/审核',group:'otc',p:'P0'},{id:'monitor',label:'撮合/争议监控',group:'otc',p:'P1'},{id:'power',label:'Power 账户',group:'otc',p:'P0'}];
    var body='';
    if(tab==='order'){
      body=s.filter(['<select><option>全部方向</option><option>buy</option><option>sell</option></select><select><option>全部状态</option><option>review</option><option>partial</option><option>completed</option><option>disputed</option></select>'])+
        s.tbl(['Order ID','方向','用户','价格','数量','已成交','状态','Power','风险','时间','操作'],
          MOCK.otcOrders.map(function(o){return'<tr><td class="cell-mono">'+o.order_id+'</td><td>'+(o.side==='buy'?'<span class="tag tag-green">Buy</span>':'<span class="tag tag-red">Sell</span>')+'</td><td>'+o.user+'</td><td>'+o.price+'</td><td class="ta-r">'+o.qty_apt+'</td><td class="ta-r">'+o.filled+'</td><td>'+s.tag(o.status)+'</td><td class="ta-r">'+(o.power_frozen||o.power_consumed||'—')+'</td><td>'+s.tag(o.risk)+'</td><td>'+o.created+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.nav(\'otc\',\'detail\')">详情</button>'+(o.status==='review'?'<button class="btn btn-xs btn-success" onclick="App.openOtcDecision(\''+o.order_id+'\',\'approve\')">通过</button><button class="btn btn-xs btn-danger" onclick="App.openOtcDecision(\''+o.order_id+'\',\'reject\')">驳回</button>':'')+'</div></td></tr>';}));
    }else if(tab==='detail'){
      var o=MOCK.otcOrders[0];
      body=s.banner('info','审核的是"订单能否继续"——查看冻结、Power、Trade、Ledger 全链路。决定必须写 reason。')+
        '<div class="card"><div class="card-header">Order Header</div>'+
          '<div class="detail-grid mb-16">'+['用户|'+o.user,'方向|'+(o.side==='buy'?'<span class="tag tag-green">Buy</span>':'<span class="tag tag-red">Sell</span>'),'价格|'+o.price,'数量|'+o.qty_apt+' APT','已成交|'+o.filled,'状态|'+s.tag(o.status),'Power Frozen|'+(o.power_frozen||'—'),'风险|'+s.tag(o.risk)].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div>'+
          '<div class="tabs"><div class="tab active">Trades</div><div class="tab">Freeze</div><div class="tab">Timeline</div><div class="tab">Risk Evidence</div></div>'+
          '<div class="mt-16">'+s.tbl(['Trade ID','Buyer','Seller','Qty','Price','Status'],MOCK.otcTrades.map(function(t){return'<tr><td class="cell-mono">'+t.trade_id+'</td><td>'+t.buyer+'</td><td>'+t.seller+'</td><td class="ta-r">'+t.qty_apt+'</td><td>'+t.price+'</td><td>'+s.tag(t.status)+'</td></tr>';}))+'</div>'+
          '<div class="btn-group mt-16"><button class="btn btn-success" onclick="App.openOtcDecision(\'OTC-001\',\'approve\')">审批通过</button><button class="btn btn-danger" onclick="App.openOtcDecision(\'OTC-001\',\'reject\')">审批驳回</button><button class="btn btn-warn" onclick="App.openOtcDecision(\'OTC-001\',\'needs_info\')">需要补充</button><button class="btn" onclick="App.openInternalNote(\'otc\',\'OTC-001\')">内部备注</button></div>'+
        '</div>';
    }else if(tab==='monitor'){
      body=s.banner('info','撮合/争议/Power 监控（P1）— 监控匹配质量、未成交、争议和 Power 冻结异常。')+
        '<div class="stat-grid">'+['匹配率|92.3%','平均成交时长|2.3 min','未成交率|4.1%','过期率|1.5%','争议中|2','异常冻结|3'].map(function(x){var p=x.split('|');return'<div class="stat-card"><div class="s-label">'+p[0]+'</div><div class="s-value">'+p[1]+'</div></div>';}).join('')+'</div>'+
        s.tbl(['订单','状态','匹配次数','剩余','冻结 Power','异常原因','操作'],
          [{order:'OTC-004',status:'disputed',matches:3,remaining:'10,000',frozen:400,anomaly:'异常集群'},{order:'OTC-006',status:'cancelled',matches:0,remaining:'500',frozen:20,anomaly:'超时 72h'}].map(function(o){return'<tr><td class="cell-mono">'+o.order+'</td><td>'+s.tag(o.status)+'</td><td class="ta-r">'+o.matches+'</td><td class="ta-r">'+o.remaining+'</td><td class="ta-r">'+o.frozen+'</td><td>'+o.anomaly+'</td><td><div class="btn-group"><button class="btn btn-xs btn-warn" onclick="App.toast(\'Case 已创建\')">创建 Case</button></div></td></tr>';}))+
        '<p class="muted" style="padding:8px 0;">P1 — 订单中心+详情已覆盖核心运营能力，此监控页可后续上线。</p>';
    }else{
      body=s.banner('info','把 Power 当资源账管理。只读为主，不可直接手改。')+
        '<div class="card">'+s.tbl(['User ID','用户','Available','Frozen','Consumed','Cap','Robot Lv'],
          MOCK.powerAccounts.map(function(p){return'<tr><td class="cell-mono">'+p.user_id+'</td><td>'+p.user+'</td><td class="ta-r highlight">'+p.available+'</td><td class="ta-r">'+p.frozen+'</td><td class="ta-r">'+p.consumed+'</td><td class="ta-r">'+p.cap+'</td><td>Lv.'+p.robot_level+(p.status==='suspended'?'<span class="tag tag-red" style="margin-left:8px;">suspended</span>':'')+'</td></tr>';}))+'</div>';
    }
    sec.innerHTML='<div class="page-header"><h2>OTC 与 Power</h2></div>'+s.tabs(ts,tab)+body;
  },

  /* ===== 06 赛事预测 ===== */
  rMarket:function(sec,tab){
    var s=this,ts=[
      {id:'list',label:'赛事/竞猜列表',group:'market',p:'P0'},
      {id:'detail',label:'竞猜详情',group:'market',p:'P0'},
      {id:'orders',label:'参与订单管理',group:'market',p:'P0'},
      {id:'result',label:'结果/结算',group:'market',p:'P0'},
      {id:'refund',label:'退款/更正',group:'market',p:'P0'},
      {id:'dataCockpit',label:'数据驾驶舱',group:'market',p:'P1'},
      {id:'footballData',label:'足球数据管理',group:'market',p:'P1'},
      {id:'marketData',label:'市场赔率数据',group:'market',p:'P1_CONDITIONAL'},
      {id:'signal',label:'信号与数据质量',group:'market',p:'P1_CONDITIONAL'}
    ];
    var body='';
    if(tab==='list'){
      body=s.filter(['<input placeholder="搜索赛事"><select><option>全部状态</option><option>draft</option><option>open</option><option>locked</option></select>'])+
        s.tbl(['Market ID','赛事','联赛','Template','状态','H/D/A','订单','APT','开赛','风险','操作'],
          MOCK.markets.map(function(m){return'<tr><td class="cell-mono">'+m.market_id+'</td><td>'+m.event+'</td><td>'+m.league+'</td><td class="cell-mono">'+m.template+'</td><td>'+s.tag(m.status)+'</td><td class="cell-mono">'+(m.home_odds||'—')+'/'+(m.draw_odds||'—')+'/'+(m.away_odds||'—')+'</td><td class="ta-r">'+m.total_orders+'</td><td class="ta-r">'+m.total_apt+'</td><td>'+m.kickoff+'</td><td>'+s.tag(m.risk)+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.nav(\'market\',\'detail\')">详情</button>'+(m.status==='draft'?'<button class="btn btn-xs btn-success" onclick="App.openMarketAction(\''+m.market_id+'\',\'publish\')">发布</button>':m.status==='open'?'<button class="btn btn-xs btn-warn" onclick="App.openMarketAction(\''+m.market_id+'\',\'pause\')">暂停</button>':'')+'</div></td></tr>';}));
    }else if(tab==='detail'){
      var mk=MOCK.markets[2];
      body=s.banner('info','三方向结构一定可见，但内部风控算法不暴露。锁定失败要有明确 reason 和 refund 路径。')+
        '<div class="card"><div class="card-header">Event Header — '+mk.event+'</div>'+
          '<div class="detail-grid col4 mb-16">'+['联赛|'+mk.league,'状态|'+s.tag(mk.status),'开赛|'+mk.kickoff,'锁定|'+mk.lock_time,'总订单|'+mk.total_orders,'总 APT|'+mk.total_apt,'Result|'+(mk.result||'pending'),'Risk|'+s.tag(mk.risk)].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv'+(p[0]==='总 APT'?' highlight':'')+'">'+p[1]+'</div></div>';}).join('')+'</div>'+
          '<div class="stat-grid">'+['Home Win|'+(mk.home_odds||'—'),'Draw|'+(mk.draw_odds||'—'),'Away Win|'+(mk.away_odds||'—')].map(function(x){var p=x.split('|');return'<div class="stat-card"><div class="s-label">'+p[0]+'</div><div class="s-value">'+p[1]+'</div></div>';}).join('')+'</div>'+
          '<div class="tabs mt-16"><div class="tab active">Orders</div><div class="tab">Liquidity</div><div class="tab">Snapshots</div></div>'+
          '<div class="mt-16">'+s.tbl(['Order ID','User','Selection','APT','状态'],MOCK.predictionOrders.filter(function(o){return o.market.indexOf(mk.event)>=0;}).map(function(o){return'<tr><td class="cell-mono">'+o.order_id+'</td><td>'+o.user+'</td><td>'+o.selection+'</td><td class="ta-r">'+o.amount_apt+'</td><td>'+s.tag(o.status)+'</td></tr>';}))+'</div>'+
          '<div class="btn-group mt-16"><button class="btn btn-warn" onclick="App.openMarketAction(\'MKT-003\',\'lockEval\')">运行 Lock Evaluation</button><button class="btn" onclick="App.nav(\'market\',\'result\')">去 Result/Settlement</button></div>'+
        '</div>';
    }else if(tab==='result'){
      body=s.banner('info','赛果确认 ≠ 钱已结算。Result Confirmer 和 Settlement Approver 分离。未 reconciliation=0 不得关闭 batch。')+
        '<div class="card"><div class="card-header">Result & Settlement</div>'+
          '<div class="detail-grid col3 mb-16">'+['Market|MKT-003 FCB vs RMD','Result|HOME (confirmed)','Evidence|BetBurger + API Feed','Primary|BetBurger','Secondary|API Feed','Settlement Status|completed'].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div>'+
          s.tbl(['Batch ID','Market','Result','Orders','Win','Payout','Fee','Recon','Settled'],MOCK.settlementBatches.map(function(b){return'<tr><td class="cell-mono">'+b.batch_id+'</td><td>'+b.market+'</td><td><span class="tag tag-green">'+b.result+'</span></td><td class="ta-r">'+b.orders_total+'</td><td class="ta-r">'+b.win_orders+'</td><td class="ta-r highlight">'+b.total_payout+'</td><td class="ta-r">'+b.fee+'</td><td>'+(b.reconciled?'<span class="tag tag-green">diff='+b.recon_diff+'</span>':'<span class="tag tag-amber">Pending</span>')+'</td><td>'+b.settled+'</td></tr>';}))+
          '<div class="btn-group mt-16"><button class="btn" onclick="App.openSettlementPreview()">模拟计算</button><button class="btn btn-success" onclick="App.openSettlementPreview()">提交 Settlement 审批</button></div>'+
        '</div>';
    }else if(tab==='orders'){
      body=s.filter(['<input placeholder="搜索订单"><select><option>全部状态</option><option>submitted</option><option>locked</option><option>awaiting_result</option><option>settled</option><option>refunding</option></select>'])+
        s.tbl(['Order ID','Market','用户','Selection','APT','状态','提交时间','结算时间'],
          MOCK.predictionOrders.map(function(o){return'<tr><td class="cell-mono">'+o.order_id+'</td><td>'+o.market+'</td><td>'+o.user+'</td><td>'+o.selection+'</td><td class="ta-r highlight">'+o.amount_apt+'</td><td>'+s.tag(o.status)+'</td><td>'+(o.created||'—')+'</td><td>'+(o.settled||'—')+'</td></tr>';}));
    }else if(tab==='refund'){
      body=s.banner('warn','这是救火页——Refund/Correction 不覆盖 old snapshot、保留原订单。高危，必须证据 + 审批。')+
        '<div class="card"><div class="card-header">Refund & Correction Cases</div>'+
          s.tbl(['Case ID','Market','类型','理由','状态','受影响订单','Impact','创建','执行'],
            MOCK.refundCorrections.map(function(r){return'<tr><td class="cell-mono">'+r.case_id+'</td><td>'+r.market+'</td><td>'+s.tag(r.type)+'</td><td>'+r.reason+'</td><td>'+s.tag(r.status)+'</td><td class="ta-r">'+r.affected_orders+'</td><td class="ta-r highlight">'+(r.principal_apt||r.impact_apt)+'</td><td>'+r.created+'</td><td>'+(r.executed||'—')+'</td></tr>';}))+
          '<div class="btn-group mt-16"><button class="btn btn-primary" onclick="App.openRefundForm(\'refund\')">新建退款</button><button class="btn btn-warn" onclick="App.openRefundForm(\'correction\')">新建更正</button></div>'+
        '</div>';
    }else if(tab==='dataCockpit'){
      body=s.banner('info','数据驾驶舱 — 运营数据仪表板依赖 DataProvider 合同和 MarketFeed 对象，均未在 05 中正式冻结。P0 运营总览已覆盖核心 KPI 摘要。')+
        '<p style="padding:16px;">Provider 连接、数据延迟监控、覆盖率仪表板等功能待 Provider 合同签署后开放。当前仅占位。</p>';
    }else if(tab==='footballData'){
      body=s.banner('info','足球数据管理 — FootballEventNormalized 对象未在 05 中正式冻结。当前为规划预览，显示数据结构和字段占位。')+
        s.tbl(['Event ID','赛事','联赛','开赛','数据源','归一化','质量','操作'],[
          '<tr><td class="cell-mono">EVT-001</td><td>ARS vs MCI</td><td>Premier League</td><td>2024-06-15 20:00</td><td>API-Football</td><td>✅</td><td class="ta-r">98%</td><td>—</td></tr>',
          '<tr><td class="cell-mono">EVT-002</td><td>LFC vs MNU</td><td>Premier League</td><td>2024-06-12 17:30</td><td>API-Football</td><td>✅</td><td class="ta-r">95%</td><td>—</td></tr>',
          '<tr><td class="cell-mono">EVT-003</td><td>FCB vs RMD</td><td>La Liga</td><td>2024-06-08 21:00</td><td>API-Football</td><td>✅</td><td class="ta-r">99%</td><td>—</td></tr>'
        ])+
        '<p class="muted" style="padding:8px 0;">仅定义数据流方向，FootballEventNormalized 对象未正式冻结，暂不可操作。</p>';
    }else if(tab==='marketData'){
      body=s.gap('市场/赔率/套利原始数据')+
        '<p style="padding:16px;">BetBurger Prematch/Live Feed 和 ArbitrageOpportunity 未在 05 中正式冻结。Provider 合同未签署（CONTRACT_GAP）。当前不可用。</p>';
    }else if(tab==='signal'){
      body=s.gap('AI 信号与数据质量')+
        '<p style="padding:16px;">AISignal / SignalEngine 未在 05 中正式冻结。AI 信号质量页不可用。</p>';
    }
    sec.innerHTML='<div class="page-header"><h2>赛事预测</h2></div>'+s.tabs(ts,tab)+body;
  },

  /* ===== 07 风控 / 审批 / 参数 / 策略 V2.4.1 ===== */
  rRisk:function(sec,tab){
    var s=this,ts=[
      {id:'case',label:'风险事件',group:'risk',p:'P0'},
      {id:'approval',label:'审批中心',group:'risk',badge:MOCK.stats.pendingApprovals,p:'P0'},
      {id:'paramDef',label:'参数定义与候选值',group:'risk',p:'P0'},
      {id:'paramRelease',label:'参数发布与快照',group:'risk',p:'P0'},
      {id:'policy',label:'策略矩阵',group:'risk',p:'P0'},
      {id:'emergency',label:'紧急操作',group:'risk',p:'P0'},
      {id:'aiCockpit',label:'AI运营驾驶舱',group:'risk',p:'P1'},
      {id:'aiSuggest',label:'AI运营建议',group:'risk',p:'P1'},
      {id:'aiMarket',label:'AI市场分析',group:'risk',p:'P1_CONDITIONAL'},
      {id:'aiSim',label:'AI策略模拟',group:'risk',p:'P1_CONDITIONAL'},
      {id:'aiPred',label:'AI竞猜助手',group:'risk',p:'P1_CONDITIONAL'},
      {id:'aiSupport',label:'AI客服风险助手',group:'risk',p:'P1'},
      {id:'report',label:'运营与经济报表',group:'risk',p:'P1'}
    ];
    var body='';
    if(tab==='case'){
      body=s.banner('warn','Analyst 和 Approver 分离。内部知道得更多≠把模型细节展示给用户。')+
        s.filter(['<select><option>全部严重度</option><option>critical</option><option>high</option></select><select><option>全部状态</option><option>analyzing</option><option>pending_approval</option></select>'])+
        s.tbl(['Case ID','类型','严重度','状态','对象','分析师','Flags','SLA','操作'],
          MOCK.riskCases.map(function(r){var bt='';if(r.status==='analyzing')bt='<button class="btn btn-xs btn-success" onclick="App.openRiskDecision(\''+r.case_id+'\',\'recommend_approve\')">推荐通过</button><button class="btn btn-xs btn-danger" onclick="App.openRiskDecision(\''+r.case_id+'\',\'recommend_reject\')">推荐驳回</button>';else if(r.status==='pending_approval')bt='<button class="btn btn-xs btn-success" onclick="App.openRiskDecision(\''+r.case_id+'\',\'approve\')">审批通过</button><button class="btn btn-xs btn-danger" onclick="App.openRiskDecision(\''+r.case_id+'\',\'reject\')">审批驳回</button>';else if(r.status==='escalated')bt='<button class="btn btn-xs btn-danger" onclick="App.openRiskDecision(\''+r.case_id+'\',\'escalate\')">紧急处置</button>';else bt='<button class="btn btn-xs">查看</button>';
          return'<tr><td class="cell-mono">'+r.case_id+'</td><td>'+r.type.replace(/_/g,' ')+'</td><td>'+s.tag(r.severity)+'</td><td>'+s.tag(r.status)+'</td><td>'+r.subject+'</td><td>'+r.analyst+'</td><td>'+(r.flags||'').split(',').map(function(f){return'<span class="tag tag-amber" style="margin-right:4px;">'+f+'</span>';}).join('')+'</td><td>'+r.sla+'</td><td><div class="btn-group">'+bt+'</div></td></tr>';}));
    }else if(tab==='approval'){
      body=s.banner('warn','Approved ≠ Executed。审批通过只是允许执行，后续异步任务可能失败。')+
        s.filter(['<select><option>全部类型</option></select>'])+
        s.tbl(['Task ID','类型','标题','请求人','风险','影响','状态','SLA','MFA','操作'],
          MOCK.approvalTasks.map(function(a){return'<tr><td class="cell-mono">'+a.task_id+'</td><td>'+s.tag(a.type.replace(/_/g,' '))+'</td><td>'+a.title+'</td><td>'+a.requester+'</td><td>'+s.tag(a.risk)+'</td><td>'+a.impact+'</td><td>'+s.tag(a.status)+'</td><td>'+a.sla+'</td><td>'+(a.needs_mfa?'<span class="tag tag-amber">Required</span>':'—')+'</td><td><div class="btn-group">'+(a.status==='pending'?'<button class="btn btn-xs btn-success" onclick="App.openApprovalDetail(\''+a.task_id+'\')">审阅</button>':a.status==='executed'?'<button class="btn btn-xs" onclick="App.openExecutionDetail(\''+a.task_id+'\')">查看执行</button>':'<button class="btn btn-xs btn-warn" onclick="App.openApprovalDetail(\''+a.task_id+'\')">修改</button>')+'</div></td></tr>';}));
    }else if(tab==='paramDef'){
      body=s.banner('info','保存 Candidate 不生效。Candidate → Approval → Release → Activate 才能上线。')+
        '<div class="card">'+s.tbl(['Namespace','Key','Type','Current Release','Active Since','Candidate','操作'],
          MOCK.parameters.map(function(p){return'<tr><td class="cell-mono">'+p.namespace+'</td><td class="cell-mono">'+p.key+'</td><td>'+p.type+'</td><td class="cell-mono">'+p.current_release+'</td><td>'+p.current_active+'</td><td>'+(p.candidate?'<span class="tag tag-amber">'+p.candidate.status+'</span> by '+p.candidate.editor:'<span class="muted">—</span>')+'</td><td><div class="btn-group"><button class="btn btn-xs btn-primary" onclick="App.openParameterEditor(\''+p.key+'\')">编辑 Candidate</button><button class="btn btn-xs" onclick="App.nav(\'risk\',\'paramRelease\')">发布历史</button></div></td></tr>';}))+'</div>';
    }else if(tab==='paramRelease'){
      body=s.banner('info','Release 不可变。每次变更用新 Release，不修改旧版。线上值必须可追溯到具体 Release。')+
        '<div class="card">'+s.tbl(['Release ID','Parameter','Version','状态','生效','Diff','操作'],
          MOCK.releaseSnapshots.map(function(r){return'<tr><td class="cell-mono">'+r.release_id+'</td><td class="cell-mono">'+r.parameter+'</td><td>'+r.version+'</td><td>'+s.tag(r.status)+'</td><td>'+r.effective+'</td><td>'+r.diff+'</td><td><div class="btn-group">'+(r.status==='active'?'<button class="btn btn-xs btn-danger" onclick="App.openReleaseAction(\''+r.release_id+'\',\'pause\')">暂停</button><button class="btn btn-xs btn-warn" onclick="App.openReleaseAction(\''+r.release_id+'\',\'rollback\')">回滚</button>':'<button class="btn btn-xs">查看快照</button>')+'</div></td></tr>';}))+'</div>';
    }else if(tab==='policy'){
      body=s.banner('info','Fail-Closed 原则。无证据不能 ALLOW。用户保护优先级最高。')+
        '<div class="card"><div class="card-header">地区准入矩阵</div><div class="table-wrap policy-matrix"><table><thead><tr><th>Region</th><th>KYC</th><th>Robot</th><th>Prediction</th><th>OTC</th><th>Self-Exclusion</th><th>Cooling-Off</th></tr></thead><tbody>'+
        ['🇺🇸 United States|L2|Yes|Yes (P0)|Yes|Not available|24h',
         '🇯🇵 Japan|L1|Yes|Age 18+|Yes|Available|48h',
         '🇰🇷 South Korea|L2|Yes|Age 19+|Limit 5K|Available|N/A',
         '🇨🇳 China|N/A|Restricted|Restricted|Restricted|N/A|N/A',
         '🇬🇧 United Kingdom|L2|Yes|Yes (P0)|Yes|Available|24h'].map(function(r){var p=r.split('|');
          return'<tr><td>'+p[0]+'</td><td class="'+(p[1]==='N/A'?'deny':'allow')+'">'+p[1]+'</td><td class="'+(p[2]==='Restricted'?'deny':'allow')+'">'+p[2]+'</td><td class="'+(p[3].match(/\d+\+/)||p[3].indexOf('Age')>=0?'conditional':p[3]==='Restricted'?'deny':'allow')+'">'+p[3]+'</td><td class="'+(p[4].indexOf('Limit')>=0?'conditional':p[4]==='Restricted'?'deny':'allow')+'">'+p[4]+'</td><td class="'+(p[5]==='Not available'||p[5]==='N/A'?'deny':'allow')+'">'+p[5]+'</td><td class="'+(p[6]==='N/A'?'deny':'conditional')+'">'+p[6]+'</td></tr>';}).join('')+'</tbody></table></div></div>';
    }else if(tab==='emergency'){
      body=s.banner('danger','紧急操作只允许预授权角色执行。需 case_id、理由、双人授权。事后补审超时必须升级。')+
        '<div class="card">'+s.tbl(['Action ID','类型','目标','理由','状态','执行人','审批人','执行时间','事后复审'],
          MOCK.emergencyActions.map(function(e){return'<tr><td class="cell-mono">'+e.action_id+'</td><td><span class="tag tag-red">'+e.type.replace(/_/g,' ')+'</span></td><td>'+e.target+'</td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="'+e.reason+'">'+e.reason+'</td><td>'+s.tag(e.status)+'</td><td>'+e.executor+'</td><td>'+e.approver+'</td><td>'+(e.executed||e.created||'—')+'</td><td>'+(e.post_review_status?s.tag(e.post_review_status)+' (by '+e.post_deadline+')':'—')+'</td></tr>';}))+
          '<div class="btn-group mt-16"><button class="btn btn-danger" onclick="App.openEmergencyForm()">发起紧急操作</button><span class="muted" style="margin-left:12px;">影响资产/账本/资格的操作默认仍需双人授权</span></div>'+
        '</div>';
    }else if(tab==='aiCockpit'){
      body=s.banner('info','依赖能力尚未就绪 — AI 运营驾驶舱依赖 AIAnalysis / AIPipeline 对象，未冻结于 05。当前仅支持占位查看。')+
        '<p style="padding:16px;">AI 建议汇总、异常检测、待确认任务等功能依赖下游 AI 服务。AI 不直接执行高风险动作。待 05 正式授权后开放。</p>';
    }else if(tab==='aiSuggest'){
      body=s.banner('info','依赖能力尚未就绪 — AI 运营建议依赖 AIRecommendation 对象，未冻结于 05。当前仅支持占位查看。')+
        '<p style="padding:16px;">AI 提供运营和市场建议，但建议对象未在 05 正式冻结。待 05/06 明确对象定义和 Provider 合同后开放此功能。</p>';
    }else if(tab==='aiMarket'){
      body=s.gap('AI市场分析')+
        '<p style="padding:16px;">AI 市场分析依赖 MarketFeed / DataProvider / AIAnalysis 对象，当前均未冻结于 05。</p>';
    }else if(tab==='aiSim'){
      body=s.gap('AI套利策略模拟')+
        '<p style="padding:16px;">AI 策略模拟依赖 ArbitrageOpportunity / AIStrategy / SimulationRun 对象，未冻结于 05。UI 规格完成（UI_SPEC=PASS），Provider/Runtime 未验证。AI_REAL_EXECUTION = DISABLED。</p>';
    }else if(tab==='aiPred'){
      body=s.gap('AI竞猜运营助手')+
        '<p style="padding:16px;">AI 竞猜助手依赖 AI 建议 Pipeline 和 MarketReadiness 对象，未冻结于 05（CONTRACT_GAP）。</p>';
    }else if(tab==='aiSupport'){
      body=s.banner('info','依赖能力尚未就绪 — AI客服/风险助手依赖 AIAnalysis 对象，未在 05 中冻结。当前仅支持占位查看。')+
        '<p style="padding:16px;">AI 分群、工单摘要、回复草稿、风险证据整理等功能依赖 AIPipeline，上游契约未冻结。待 05 正式授权后开放。</p>';
    }else if(tab==='report'){
      body=s.banner('info','运营与经济报表 — 生成用户/代理/Robot/Reward/Power/竞猜/OTC/数据源/AI建议效果报表。运营总览已覆盖核心指标。')+
        '<div class="card">'+s.tbl(['报表类型','时间范围','数据版本','生成时间','状态','操作'],[
          '<tr><td>日运营报表</td><td>2024-06-10</td><td>Snapshot #124</td><td>2024-06-11 00:05</td><td><span class="tag tag-green">已生成</span></td><td><button class="btn btn-xs" onclick="App.toast(\'报表下载中\')">下载</button></td></tr>',
          '<tr><td>周经济报表</td><td>2024-W23</td><td>Snapshot #W23-3</td><td>2024-06-10 00:05</td><td><span class="tag tag-green">已生成</span></td><td><button class="btn btn-xs" onclick="App.toast(\'报表下载中\')">下载</button></td></tr>',
          '<tr><td>月度审计报表</td><td>2024-05</td><td>Snapshot #M05</td><td>2024-06-01 00:05</td><td><span class="tag tag-green">已生成</span></td><td><button class="btn btn-xs" onclick="App.toast(\'报表下载中\')">下载</button></td></tr>'
        ])+'</div>'+
        '<p class="muted" style="padding:8px 0;">运营总览覆盖核心指标，报表可后续上线。</p>';
    }
    sec.innerHTML='<div class="page-header"><h2>风控 / 审批 / 参数 / 策略</h2></div>'+s.tabs(ts,tab)+body;
  },

  openApprovalDetail:function(tid){
    var a=MOCK.approvalTasks.find(function(x){return x.task_id===tid;}),s=this;
    if(!a)return;
    s.openModal('审批详情 — '+a.task_id,
      '<div class="detail-grid mb-16">'+['请求人|'+a.requester,'风险|'+s.tag(a.risk),'影响|'+a.impact].map(function(x){var p=x.split('|');return'<div class="detail-item"><div class="dl">'+p[0]+'</div><div class="dv">'+p[1]+'</div></div>';}).join('')+'</div>'+
      '<div class="diff-table"><table><tr><td class="diff-key">旧值</td><td class="diff-old">'+a.old_value+'</td></tr><tr><td class="diff-arrow">→</td></tr><tr><td class="diff-key">新值</td><td class="diff-new">'+a.new_value+'</td></tr></table></div>'+
      '<div class="divider"></div><div class="approval-flow"><div class="approval-step done"><div class="step-dot">✓</div><div class="step-label">草稿</div></div><div class="approval-line done"></div><div class="approval-step active"><div class="step-dot">●</div><div class="step-label">审批中</div></div><div class="approval-line"></div><div class="approval-step"><div class="step-dot">○</div><div class="step-label">执行</div></div></div>',
      '<button class="btn btn-danger" onclick="App.closeModal();App.toast(\'已驳回\',\'error\')">驳回</button><button class="btn btn-warn" onclick="App.closeModal();App.toast(\'请求修改\')">请求修改</button><button class="btn btn-success" onclick="App.closeModal();App.toast(\'已批准 — 等待异步执行\')">批准</button>',true);
  },

  /* ===== 08 客服 / 审计 / 运维 ===== */
  rSupport:function(sec,tab){
    var s=this,ts=[
      {id:'audit',label:'全量操作日志',group:'support',p:'P0'},
      {id:'sensitiveAudit',label:'敏感操作审计',group:'support',p:'P0'},
      {id:'ops',label:'异步任务/状态',group:'support',p:'P0'},
      {id:'provider',label:'Provider 监控',group:'support',p:'P1_CONDITIONAL'},
      {id:'datasource',label:'数据源管理',group:'support',p:'P1_CONDITIONAL'},
      {id:'rbac',label:'RBAC 角色',group:'support',p:'P0'},
      {id:'lang',label:'语言管理',group:'support',p:'P0'},
      {id:'config',label:'系统配置',group:'support',p:'P0'},
      {id:'migration',label:'APT Migration',group:'support',p:'FUTURE'}
    ];
    var body='';
    if(tab==='sensitiveAudit'){
      body=s.banner('info','敏感操作审计 — 聚焦余额调整、冻结、参数发布、结算更正、权限变化。仅超级管理员。')+
        s.tbl(['事件','操作者','对象','Before','After','原因','证据','审批人','执行结果','时间'],[
          '<tr><td>资产调整</td><td>admin</td><td>U-004</td><td>46,700</td><td>48,200</td><td>工单补偿</td><td>TKT-033</td><td>risk_approver_01</td><td><span class="tag tag-green">已执行</span></td><td>2024-06-05</td></tr>',
          '<tr><td>参数发布</td><td>param_editor_01</td><td>REL-3.2.1</td><td>V3.2.0</td><td>V3.2.1</td><td>简化56级表</td><td>—</td><td>admin</td><td><span class="tag tag-green">已激活</span></td><td>2024-06-10</td></tr>',
          '<tr><td>权限变化</td><td>admin</td><td>cs_agent_02</td><td>support</td><td>support+escalation</td><td>紧急升级授权</td><td>—</td><td>security_admin</td><td><span class="tag tag-green">已执行</span></td><td>2024-06-10</td></tr>'
        ])+
        '<p class="muted" style="padding:8px 0;">append-only，不可删除。</p>';
    }else if(tab==='provider'){
      body=s.gapLite('DataProvider / ProviderHealth 对象未冻结于 05')+
        '<div class="card"><div class="card-header">Provider 监控 — 规划预览</div>'+
        s.tbl(['Provider','类型','状态','延迟','成功率','Quota','上次成功','上次失败','合同状态'],[
          '<tr><td><strong>API-Football</strong></td><td>足球数据</td><td>—</td><td class="ta-r">—</td><td class="ta-r">—</td><td class="ta-r">—</td><td>—</td><td>—</td><td><span class="tag tag-amber">CONTRACT_GAP</span></td></tr>',
          '<tr><td><strong>BetBurger</strong></td><td>市场数据</td><td>—</td><td class="ta-r">—</td><td class="ta-r">—</td><td class="ta-r">—</td><td>—</td><td>—</td><td><span class="tag tag-amber">CONTRACT_GAP</span></td></tr>'
        ])+'</div>'+
        '<p class="muted" style="padding:8px 0;">UI_SPEC = VERIFIED_PASS，PROVIDER_CONTRACT = CONTRACT_GAP，RUNTIME = NOT_YET_EXECUTED。</p>';
    }else if(tab==='datasource'){
      body=s.gapLite('DataProvider 对象未冻结于 05')+
        '<div class="card"><div class="card-header">数据源管理 — 规划预览</div>'+
        '<p style="padding:16px;">API-Football 和 BetBurger 的 Provider 合同未签署（CONTRACT_GAP），Runtime 验证未执行。当前仅显示 Provider 名称占位。</p>'+
        '</div>';
    }else if(tab==='migration'){
      body='<div class="banner banner-info">APT Migration — 功能预留（FUTURE）。APT-I → APT-C 管理入口，当前 P0 保持 Closed。</div>'+
        '<div class="card"><p class="muted" style="padding:16px;">Future 占位，不是当前运营任务。后台有页面不代表已开放。</p></div>';
    }else if(tab==='audit'){
      body=s.banner('info','任何"谁改了什么"都应该在这里回答。审计日志不可编辑/删除。')+
        s.filter(['<input placeholder="操作人"><input type="date">'])+
        s.tbl(['ID','操作人','动作','目标','结果','详情','时间','IP'],
          MOCK.auditLog.map(function(l){return'<tr><td class="cell-mono">'+l.id+'</td><td>'+l.actor+'</td><td>'+l.action.replace(/_/g,' ')+'</td><td class="cell-mono">'+l.target+'</td><td>'+s.tag(l.result)+'</td><td>'+l.detail+'</td><td>'+l.time+'</td><td class="cell-mono">'+l.ip+'</td></tr>';}));
    }else if(tab==='ops'){
      body=s.banner('info','运维重试的是任务，不是重做一次用户订单。资金任务需额外确认。')+
        '<div class="card"><div class="card-header">Async Jobs</div>'+
          s.tbl(['Job ID','类型','目标','状态','进度','记录','错误','重试','操作'],
            MOCK.asyncJobs.map(function(j){return'<tr><td class="cell-mono">'+j.job_id+'</td><td>'+j.type.replace(/_/g,' ')+'</td><td>'+j.target+'</td><td>'+s.tag(j.status)+'</td><td>'+(j.progress||(j.status==='completed'?'100%':j.status==='running'?'72%':'—'))+'</td><td class="ta-r">'+(j.records||'—')+'</td><td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;">'+(j.error||'—')+'</td><td class="ta-r">'+(j.retries||0)+'</td><td><div class="btn-group">'+(j.status==='failed'?'<button class="btn btn-xs btn-warn" onclick="App.openJobMonitor(\''+j.job_id+'\',\'retry\')">重试</button>':j.status==='running'?'<button class="btn btn-xs" onclick="App.openJobMonitor(\''+j.job_id+'\')">监控</button>':j.status==='completed'&&j.dlq?'<button class="btn btn-xs btn-warn" onclick="App.openJobMonitor(\''+j.job_id+'\')">查看 DLQ</button>':'<button class="btn btn-xs" onclick="App.openJobMonitor(\''+j.job_id+'\')">查看</button>')+'</div></td></tr>';}))+
        '</div>'+
        '<div class="card mt-16"><div class="card-header">系统状态</div>'+
          Object.keys(MOCK.systemStatus).map(function(k){var v=MOCK.systemStatus[k];return'<div class="flex-row" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span style="color:var(--gray-500);width:140px;">'+k+'</span><span class="tag tag-'+(v==='Normal'||v==='Running'?'green':'amber')+'">'+v+'</span></div>';}).join('')+
        '</div>';
    }else if(tab==='rbac'){
      body=s.banner('info','SoD：Param Edit ≠ Approval ≠ Activation；Risk Analysis ≠ High-Risk Disposition。超管不能绕过审计。')+
        '<div class="card">'+s.tbl(['ID','角色','描述','成员','操作'],MOCK.adminRoles.map(function(r){return'<tr><td>'+r.id+'</td><td><strong>'+r.name+'</strong>'+(r.is_super?'<span class="tag tag-red">Super</span>':'')+'</td><td>'+r.desc+'</td><td>'+r.members+'</td><td><button class="btn btn-xs btn-primary" onclick="App.openRoleEditor('+r.id+')">编辑</button></td></tr>';}))+'</div>'+
        '<div class="card mt-16"><div class="card-header">管理员用户</div>'+s.tbl(['ID','账号','角色','登录次数','最后登录'],MOCK.adminUsers.map(function(a){return'<tr><td>'+a.id+'</td><td class="cell-mono">'+a.account+'</td><td>'+a.role+'</td><td>'+a.login_cnt+'</td><td>'+a.last_login+'</td></tr>';}))+'</div>';
    }else if(tab==='lang'){
      body='<div class="card">'+s.tbl(['Code','语言','覆盖率','翻译条数','状态'],
        MOCK.languages.map(function(l){return'<tr><td class="cell-mono">'+l.code+'</td><td>'+l.flag+' '+l.name+'</td><td><div class="progress-bar" style="width:120px;display:inline-block;"><div class="fill fill-blue" style="width:'+l.coverage+'"></div></div> <span style="font-size:12px;margin-left:8px;">'+l.coverage+'</span></td><td>'+l.translations+'</td><td>'+s.tag(l.status)+'</td></tr>';}))+'</div>';
    }else{
      body='<div class="card">'+s.tbl(['Key','Value','描述'],MOCK.siteConfig.map(function(c){return'<tr><td class="cell-mono">'+c.key+'</td><td class="highlight">'+c.value+'</td><td>'+c.descr+'</td></tr>';}))+'</div>';
    }
    sec.innerHTML='<div class="page-header"><h2>客服 / 审计 / 运维</h2></div>'+s.tabs(ts,tab)+body;
  }
};

document.addEventListener('DOMContentLoaded',function(){App.init();});
