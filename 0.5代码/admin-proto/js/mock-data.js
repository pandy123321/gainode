/* ============================================================
   Gainode Admin V6.1 · Mock Data
   ============================================================ */
var MOCK = {

  /* ───── 01 Dashboard Stats ───── */
  stats: {
    totalUsers:28456, todayActive:3842, pendingKyc:156,
    aptInCirculation:'12,845,000', totalRevenueFiat:'$1,247,890',
    pendingApprovals:23, activeRiskCases:7, openTickets:34,
    robotActiveCount:8934, marketCount:45, otcOrderCount:156
  },

  recentActivities: [
    {type:'user',text:'新用户注册',detail:'user_f8a2b',time:'刚刚'},
    {type:'robot',text:'Robot 升级 Lv.12→Lv.13',detail:'user_3c41',time:'2分钟前'},
    {type:'otc',text:'OTC 买单成交',detail:'5,000 APT @ $1.02',time:'5分钟前'},
    {type:'prediction',text:'Market ARS vs MCI 锁定',detail:'Home/Draw/Away',time:'8分钟前'},
    {type:'kyc',text:'KYC 审核通过',detail:'user_d91c',time:'12分钟前'},
    {type:'risk',text:'风险案件创建',detail:'#RC-2024-056',time:'15分钟前'},
    {type:'reward',text:'批量 Rewards 发放完成',detail:'Batch #B-892 (1,234 users)',time:'18分钟前'},
    {type:'param',text:'参数发布 V3.2.1 已激活',detail:'daily_reward_coefficient',time:'25分钟前'},
  ],

  systemStatus: {api:'Normal',db:'Normal',redis:'Normal',betburger:'Latency 2.3s',settlement:'Running',approval:'Normal',queue:'12 pending'},

  /* ───── 02 Users & Admission ───── */
  users: [
    {id:'U-001',display_name:'Alex Chen',phone:'+1 415-***-1234',email:'alex@email.com',
     kyc_status:'approved',kyc_level:'L2',status:'active',
     global_p_level:12,robot_active:true,ai_eligible:true,prediction_eligible:true,
     registered:'2024-03-15',last_active:'2024-06-10 09:30',country:'US'},
    {id:'U-002',display_name:'Maria Lopez',phone:'+34 612-***-5678',email:'maria@email.com',
     kyc_status:'pending',kyc_level:'L1',status:'active',
     global_p_level:5,robot_active:true,ai_eligible:true,prediction_eligible:false,
     registered:'2024-04-20',last_active:'2024-06-09 18:00',country:'ES'},
    {id:'U-003',display_name:'田中 太郎',phone:'+81 90-***-9012',email:'tanaka@email.com',
     kyc_status:'rejected',kyc_level:'L0',status:'restricted',
     global_p_level:1,robot_active:false,ai_eligible:false,prediction_eligible:false,
     registered:'2024-05-01',last_active:'2024-05-28 14:00',country:'JP'},
    {id:'U-004',display_name:'John Smith',phone:'+44 7700-***-3456',email:'john@email.com',
     kyc_status:'approved',kyc_level:'L2',status:'active',
     global_p_level:28,robot_active:true,ai_eligible:true,prediction_eligible:true,
     registered:'2024-01-10',last_active:'2024-06-10 08:15',country:'GB'},
    {id:'U-005',display_name:'Sophie Martin',phone:'+33 612-***-7890',email:'sophie@email.com',
     kyc_status:'needs_info',kyc_level:'L1',status:'active',
     global_p_level:8,robot_active:true,ai_eligible:true,prediction_eligible:false,
     registered:'2024-05-18',last_active:'2024-06-08 22:00',country:'FR'},
    {id:'U-006',display_name:'박민수',phone:'+82 10-***-2345',email:'park@email.com',
     kyc_status:'approved',kyc_level:'L3',status:'suspended',
     global_p_level:45,robot_active:false,ai_eligible:true,prediction_eligible:true,
     registered:'2024-02-05',last_active:'2024-06-01 10:00',country:'KR'},
    {id:'U-007',display_name:'David Kim',phone:'+1 212-***-6789',email:'david@email.com',
     kyc_status:'review',kyc_level:'L2',status:'active',
     global_p_level:19,robot_active:true,ai_eligible:true,prediction_eligible:true,
     registered:'2024-04-01',last_active:'2024-06-10 07:00',country:'US'},
    {id:'U-008',display_name:'Anna Müller',phone:'+49 151-***-0123',email:'anna@email.com',
     kyc_status:'approved',kyc_level:'L2',status:'active',
     global_p_level:33,robot_active:true,ai_eligible:true,prediction_eligible:true,
     registered:'2024-02-20',last_active:'2024-06-10 05:30',country:'DE'},
    {id:'U-009',display_name:'Carlos Ruiz',phone:'+52 55-***-4567',email:'carlos@email.com',
     kyc_status:'not_started',kyc_level:'L0',status:'active',
     global_p_level:0,robot_active:false,ai_eligible:false,prediction_eligible:false,
     registered:'2024-06-05',last_active:'2024-06-10 00:15',country:'MX'},
    {id:'U-010',display_name:'Emma Wilson',phone:'+61 400-***-8901',email:'emma@email.com',
     kyc_status:'approved',kyc_level:'L3',status:'active',
     global_p_level:52,robot_active:true,ai_eligible:true,prediction_eligible:true,
     registered:'2023-11-15',last_active:'2024-06-10 10:00',country:'AU'},
  ],

  /* User 360 details for U-001 */
  user360: {
    id:'U-001',display_name:'Alex Chen',status:'active',
    kyc:{status:'approved',level:'L2',country:'US',documents:'ID + Utility Bill'},
    robot:{robot_id:'RB-001',level:12,status:'active',standard_capacity:500,paused:false},
    apt:{balance_apt_i:'25,430.50',frozen_apt_i:'5,000.00',total_earned:'38,200.00'},
    power:{available:1200,frozen:300,consumed:8500,recovering:0,cap:2000},
    otc:{open_buy:2,open_sell:1,completed:45,disputed:0},
    prediction:{open_orders:3,settled:128,won:72,lost:42,refunded:14},
    risk:{active_cases:0,history:2},
    tickets:{open:0,history:3},
    security:{mfa_enabled:true,devices:3,last_password_change:'2024-05-01'}
  },

  /* KYC Queue */
  kycQueue: [
    {case_id:'KYC-001',user_id:'U-002',user_name:'Maria Lopez',
     kyc_level:'L1',status:'pending',submitted:'2024-05-25',
     documents:{id_front:'ok',id_back:'ok',selfie:'pending',utility:'n/a'},
     risk_score:'low'},
    {case_id:'KYC-002',user_id:'U-005',user_name:'Sophie Martin',
     kyc_level:'L1',status:'needs_info',submitted:'2024-05-30',
     documents:{id_front:'ok',id_back:'ok',selfie:'blurry',utility:'ok'},
     risk_score:'medium',missing:'Selfie photo too blurry'},
    {case_id:'KYC-003',user_id:'U-007',user_name:'David Kim',
     kyc_level:'L2',status:'review',submitted:'2024-06-01',
     documents:{id_front:'ok',id_back:'ok',selfie:'ok',utility:'under_review'},
     risk_score:'medium'},
    {case_id:'KYC-004',user_id:'U-009',user_name:'Carlos Ruiz',
     kyc_level:'L0',status:'not_started',submitted:'n/a',
     documents:{},risk_score:'n/a'}
  ],

  /* ───── 03 Assets & Ledger ───── */
  assetOverview: {
    totalAptMinted:'15,200,000',
    aptInCirculation:'12,845,000',
    aptFrozen:'1,230,000',
    aptPendingSettlement:'180,000',
    otcReserve:{approved:500000,committed:320000,available:180000,reconciled:'2024-06-10 08:00'},
    opsBudget:{approved:200000,spent:145000,remaining:55000,reconciled:'2024-06-10 08:00'},
    reconciliation:{lastRun:'2024-06-10 08:00',diff:0,status:'matched'}
  },

  ledgerEntries: [
    {id:'LE-001',batch:'B-890',user:'U-001/Alex Chen',type:'reward_claim',dir:'in',qty:'+125.00',balance:'25,430.50',time:'2024-06-10 08:00',status:'posted'},
    {id:'LE-002',batch:'B-891',user:'U-004/John Smith',type:'otc_sell',dir:'out',qty:'-2,000.00',balance:'48,200.00',time:'2024-06-10 07:30',status:'posted'},
    {id:'LE-003',batch:'B-890',user:'U-008/Anna Müller',type:'reward_claim',dir:'in',qty:'+250.00',balance:'65,100.00',time:'2024-06-10 08:00',status:'posted'},
    {id:'LE-004',batch:'B-892',user:'U-010/Emma Wilson',type:'prediction_win',dir:'in',qty:'+500.00',balance:'125,300.00',time:'2024-06-10 06:00',status:'pending'},
    {id:'LE-005',batch:'B-891',user:'U-006/박민수',type:'otc_buy',dir:'in',qty:'+1,000.00',balance:'12,800.00',time:'2024-06-09 22:00',status:'posted'},
    {id:'LE-006',batch:'B-893',user:'U-001/Alex Chen',type:'robot_upgrade_cost',dir:'out',qty:'-2,500.00',balance:'22,930.50',time:'2024-06-09 18:00',status:'posted'}
  ],

  pools: [
    {name:'AI Reward Pool',balance:'3,200,000',budgeted:'5,000,000',spent_this_month:'450,000',reconciled:true,last_recon:'2024-06-10 08:00'},
    {name:'Prediction Settlement Pool',balance:'2,800,000',budgeted:'3,000,000',spent_this_month:'180,000',reconciled:true,last_recon:'2024-06-10 08:00'},
    {name:'OTC Settlement Reserve',balance:'500,000',committed:'320,000',available:'180,000',reconciled:true,last_recon:'2024-06-10 08:00'},
    {name:'Operational Budget',balance:'200,000',spent:'145,000',remaining:'55,000',reconciled:true,last_recon:'2024-06-10 08:00'}
  ],

  /* ───── 04 Robot & Rewards ───── */
  robots: [
    {robot_id:'RB-001',user:'U-001 / Alex Chen',level:12,level_group:'10-19',status:'active',
     standard_capacity:500,daily_reward_coefficient:0.25,power_cap:2000,
     eligible:true,rule_version:'V3.2.1',started:'2024-03-20',last_claim:'2024-06-10 08:00'},
    {robot_id:'RB-002',user:'U-004 / John Smith',level:28,level_group:'20-29',status:'active',
     standard_capacity:1200,daily_reward_coefficient:0.42,power_cap:5000,
     eligible:true,rule_version:'V3.2.1',started:'2024-01-15',last_claim:'2024-06-10 08:00'},
    {robot_id:'RB-003',user:'U-008 / Anna Müller',level:33,level_group:'30-39',status:'active',
     standard_capacity:2400,daily_reward_coefficient:0.58,power_cap:8000,
     eligible:true,rule_version:'V3.2.1',started:'2024-02-25',last_claim:'2024-06-10 08:00'},
    {robot_id:'RB-004',user:'U-010 / Emma Wilson',level:52,level_group:'50-56',status:'active',
     standard_capacity:8000,daily_reward_coefficient:0.92,power_cap:20000,
     eligible:true,rule_version:'V3.2.1',started:'2023-12-01',last_claim:'2024-06-10 08:00'},
    {robot_id:'RB-005',user:'U-005 / Sophie Martin',level:8,level_group:'1-9',status:'cooling',
     standard_capacity:300,daily_reward_coefficient:0.12,power_cap:800,
     eligible:false,rule_version:'V3.2.0',started:'2024-05-20',last_claim:'2024-06-08 12:00'},
    {robot_id:'RB-006',user:'U-006 / 박민수',level:45,level_group:'40-49',status:'review',
     standard_capacity:4800,daily_reward_coefficient:0.75,power_cap:16000,
     eligible:false,rule_version:'V3.2.1',started:'2024-02-10',last_claim:'2024-06-01 10:00'}
  ],

  rewards: [
    {reward_id:'RW-1001',user:'U-004 / John Smith',robot:'RB-002',std_capacity:1200,
     coeff:0.42, pending_apt:504,status:'held',batch:'B-892',period:'2024-06-10'},
    {reward_id:'RW-1002',user:'U-001 / Alex Chen',robot:'RB-001',std_capacity:500,
     coeff:0.25,pending_apt:125,status:'claimed',batch:'B-892',period:'2024-06-10',claimed_at:'2024-06-10 08:05'},
    {reward_id:'RW-1003',user:'U-008 / Anna Müller',robot:'RB-003',std_capacity:2400,
     coeff:0.58,pending_apt:1392,status:'pending_claim',batch:'B-892',period:'2024-06-10'},
    {reward_id:'RW-1004',user:'U-010 / Emma Wilson',robot:'RB-004',std_capacity:8000,
     coeff:0.92,pending_apt:7360,status:'claimed',batch:'B-892',period:'2024-06-10',claimed_at:'2024-06-10 08:02'},
    {reward_id:'RW-1005',user:'U-005 / Sophie Martin',robot:'RB-005',std_capacity:300,
     coeff:0.12,pending_apt:36,status:'expired_returned',batch:'B-890',period:'2024-06-09'}
  ],

  /* Robot Detail (RB-002 / John Smith Level 28) */
  robotDetail: {
    robot_id:'RB-002',user:'U-004 / John Smith',level:28,status:'active',
    standard_capacity:1200,daily_reward_coefficient:0.42,power_cap:5000,rule_version:'V3.2.1',
    started:'2024-01-15',total_uptime_days:147,
    upgrades:[
      {date:'2024-05-10',from:25,to:26,cost_apt:1200,power_cap_after:4800},
      {date:'2024-06-01',from:26,to:27,cost_apt:1400,power_cap_after:4900},
      {date:'2024-06-08',from:27,to:28,cost_apt:1600,power_cap_after:5000}
    ],
    rewardHistory:[
      {period:'2024-06-10',apt:504,status:'held'},
      {period:'2024-06-09',apt:480,status:'claimed'},
      {period:'2024-06-08',apt:456,status:'claimed'},
      {period:'2024-06-07',apt:432,status:'claimed'},
      {period:'2024-06-06',apt:408,status:'claimed'}
    ],
    powerLedger:[
      {date:'2024-06-10',action:'daily_recovery',qty:'+120',balance:3500},
      {date:'2024-06-09',action:'otc_sell_consumption',qty:'-200',balance:3380},
      {date:'2024-06-08',action:'robot_upgrade_consumption',qty:'-500',balance:3580},
      {date:'2024-06-08',action:'daily_recovery',qty:'+120',balance:4080}
    ]
  },

  /* ───── 05 OTC & Power ───── */
  otcOrders: [
    {order_id:'OTC-001',side:'sell',user:'U-001 / Alex Chen',price:'$1.02',qty_apt:'2,000',
     filled:0,status:'review',power_frozen:600,risk:'low',created:'2024-06-10 07:00'},
    {order_id:'OTC-002',side:'buy',user:'U-004 / John Smith',price:'$1.01',qty_apt:'5,000',
     filled:3200,status:'partial',power_consumed:200,risk:'low',created:'2024-06-09 20:00'},
    {order_id:'OTC-003',side:'sell',user:'U-008 / Anna Müller',price:'$1.03',qty_apt:'1,500',
     filled:1500,status:'completed',power_frozen:450,risk:'low',created:'2024-06-09 15:00',
     completed:'2024-06-09 18:00'},
    {order_id:'OTC-004',side:'buy',user:'U-006 / 박민수',price:'$1.04',qty_apt:'10,000',
     filled:0,status:'disputed',power_consumed:400,risk:'high',created:'2024-06-08 12:00'},
    {order_id:'OTC-005',side:'sell',user:'U-010 / Emma Wilson',price:'$1.02',qty_apt:'8,000',
     filled:8000,status:'completed',power_frozen:2400,risk:'low',created:'2024-06-09 10:00',
     completed:'2024-06-09 14:00'},
    {order_id:'OTC-006',side:'buy',user:'U-002 / Maria Lopez',price:'$1.01',qty_apt:'500',
     filled:0,status:'cancelled',power_consumed:20,risk:'low',created:'2024-06-07 09:00'}
  ],

  otcTrades: [
    {trade_id:'TR-089',buyer:'U-004 / John Smith',seller:'U-010 / Emma Wilson',
     qty_apt:'3,200',price:'$1.02',buyer_power_consumed:128,seller_power_frozen:960,
     status:'settled',created:'2024-06-09 18:00'}
  ],

  powerAccounts: [
    {user_id:'U-001',user:'Alex Chen',available:1200,frozen:300,consumed:8500,cap:2000,robot_level:12},
    {user_id:'U-004',user:'John Smith',available:3500,frozen:500,consumed:18000,cap:5000,robot_level:28},
    {user_id:'U-008',user:'Anna Müller',available:6200,frozen:800,consumed:22000,cap:8000,robot_level:33},
    {user_id:'U-010',user:'Emma Wilson',available:14500,frozen:2000,consumed:56000,cap:20000,robot_level:52},
    {user_id:'U-005',user:'Sophie Martin',available:450,frozen:150,consumed:3200,cap:800,robot_level:8},
    {user_id:'U-006',user:'박민수',available:0,frozen:0,consumed:40000,cap:0,robot_level:45,status:'suspended'}
  ],

  /* ───── 06 Prediction ───── */
  markets: [
    {market_id:'MKT-001',event:'ARS vs MCI',league:'Premier League',
     template:'FOOTBALL_PREMATCH_1X2',status:'open',kickoff:'2024-06-15 20:00',
     lock_time:'2024-06-15 19:55',home_odds:2.10,draw_odds:3.40,away_odds:3.20,
     total_orders:234,total_apt:'45,600',risk:'normal'},
    {market_id:'MKT-002',event:'LFC vs MNU',league:'Premier League',
     template:'FOOTBALL_PREMATCH_1X2',status:'closing',kickoff:'2024-06-12 17:30',
     lock_time:'2024-06-12 17:25',home_odds:1.85,draw_odds:3.60,away_odds:4.10,
     total_orders:567,total_apt:'123,400',risk:'elevated'},
    {market_id:'MKT-003',event:'FCB vs RMD',league:'La Liga',
     template:'FOOTBALL_PREMATCH_1X2',status:'settlement',kickoff:'2024-06-08 21:00',
     lock_time:'2024-06-08 20:55',home_odds:2.50,draw_odds:3.10,away_odds:2.80,
     result:'HOME',total_orders:1203,total_apt:'340,000',risk:'settled'},
    {market_id:'MKT-004',event:'JUV vs MIL',league:'Serie A',
     template:'FOOTBALL_PREMATCH_1X2',status:'draft',kickoff:'2024-06-18 20:45',
     lock_time:'TBD',home_odds:null,draw_odds:null,away_odds:null,
     total_orders:0,total_apt:'0',risk:'draft'},
    {market_id:'MKT-005',event:'PSG vs LYO',league:'Ligue 1',
     template:'FOOTBALL_PREMATCH_1X2',status:'locked',kickoff:'2024-06-11 21:00',
     lock_time:'2024-06-11 20:55',home_odds:1.45,draw_odds:4.20,away_odds:7.00,
     total_orders:890,total_apt:'198,000',risk:'high'},
    {market_id:'MKT-006',event:'BAY vs BVB',league:'Bundesliga',
     template:'FOOTBALL_PREMATCH_1X2',status:'void',kickoff:'2024-06-08 18:30',
     lock_time:'2024-06-08 18:25',home_odds:null,draw_odds:null,away_odds:null,
     reason:'Event cancelled',total_orders:0,total_apt:'0',risk:'void'}
  ],

  predictionOrders: [
    {order_id:'PO-5001',user:'U-001/Alex Chen',market:'MKT-001 ARS vs MCI',selection:'HOME',
     amount_apt:500,odds:2.10,status:'submitted',created:'2024-06-10 09:00'},
    {order_id:'PO-5002',user:'U-004/John Smith',market:'MKT-002 LFC vs MNU',selection:'DRAW',
     amount_apt:1000,odds:3.60,status:'locked',created:'2024-06-09 15:00'},
    {order_id:'PO-5003',user:'U-008/Anna Müller',market:'MKT-003 FCB vs RMD',selection:'HOME',
     amount_apt:800,odds:2.50,status:'settled',created:'2024-06-08 12:00',payout_apt:2000,settled:'2024-06-09 08:00'},
    {order_id:'PO-5004',user:'U-010/Emma Wilson',market:'MKT-003 FCB vs RMD',selection:'AWAY',
     amount_apt:2000,odds:2.80,status:'settled',created:'2024-06-08 14:00',payout_apt:0,settled:'2024-06-09 08:00'},
    {order_id:'PO-5005',user:'U-001/Alex Chen',market:'MKT-002 LFC vs MNU',selection:'HOME',
     amount_apt:300,odds:1.85,status:'locked',created:'2024-06-10 10:00'}
  ],

  /* ───── 07 Risk / Approval / Parameters / Policy ───── */
  riskCases: [
    {case_id:'RC-2024-056',type:'order_anomaly',severity:'high',status:'analyzing',
     subject:'OTC-004 / 박민수',analyst:'risk_analyst_01',assignee:'risk_approver_01',
     flags:'abnormal_pattern,large_amount',created:'2024-06-08 14:00',sla:'4h'},
    {case_id:'RC-2024-055',type:'kyc_mismatch',severity:'medium',status:'pending_approval',
     subject:'U-007 / David Kim',analyst:'risk_analyst_02',assignee:'risk_approver_01',
     flags:'utility_bill_mismatch',created:'2024-06-07 10:00',sla:'24h'},
    {case_id:'RC-2024-054',type:'market_manipulation',severity:'high',status:'escalated',
     subject:'MKT-002 LFC vs MNU',analyst:'risk_analyst_01',assignee:'admin',
     flags:'betting_pattern_cluster',created:'2024-06-10 06:00',sla:'1h'},
    {case_id:'RC-2024-053',type:'account_takeover',severity:'critical',status:'resolved',
     subject:'U-003 / 田中 太郎',analyst:'risk_analyst_01',assignee:'risk_approver_02',
     resolution:'account_restricted',created:'2024-06-05 08:00',resolved:'2024-06-05 12:00'}
  ],

  approvalTasks: [
    {task_id:'APR-089',type:'parameter_release',title:'Release V3.2.1 — daily_reward_coefficient',
     requester:'param_editor_01',risk:'low',impact:'All active robots',
     old_value:'multiple coefficients (V3.2.0)',new_value:'simplified 56-level table',
     status:'pending',created:'2024-06-10 08:00',sla:'48h'},
    {task_id:'APR-088',type:'ledger_correction',title:'Correct LE-004 posting error',
     requester:'ledger_operator_01',risk:'medium',impact:'±500 APT on Prediction Settlement pool',
     old_value:'Posting to wrong batch',new_value:'Reversal + repost to B-892',
     status:'changes_requested',created:'2024-06-09 16:00',sla:'24h'},
    {task_id:'APR-087',type:'risk_disposition',title:'RC-2024-054 Escalation — market_manipulation',
     requester:'risk_approver_01',risk:'high',impact:'MKT-002 LFC vs MNU',
     old_value:'Continue market open',new_value:'Pause and investigate cluster',
     status:'pending',created:'2024-06-10 07:00',sla:'2h',needs_mfa:true},
    {task_id:'APR-086',type:'parameter_activation',title:'Activate Release V3.2.0 -> PROD',
     requester:'release_operator_01',risk:'low',impact:'Power cap formula update',
     status:'executed',created:'2024-06-08 10:00',decided:'2024-06-08 14:00',executed:'2024-06-08 15:00'}
  ],

  parameters: [
    {namespace:'AI',key:'daily_reward_coefficient',type:'TABLE',
     current_release:'V3.2.1',current_active:'2024-06-10 08:30',
     candidate:null,status:'active'},
    {namespace:'AI',key:'power_cap_by_robot_level',type:'TABLE',
     current_release:'V3.2.0',current_active:'2024-06-01 00:00',
     candidate:{candidate_id:'C-023',editor:'param_editor_01',status:'draft',
       changes:'Lv.50-56 cap adjusted +5%'},status:'active'},
    {namespace:'AI',key:'robot_upgrade_cost_apt',type:'TABLE',
     current_release:'V3.1.0',current_active:'2024-05-01 00:00',
     candidate:null,status:'active'},
    {namespace:'Prediction',key:'min_order_apt',type:'SCALAR',
     current_release:'V1.0.0',current_active:'2024-01-01 00:00',value:'10',
     candidate:null,status:'active'},
    {namespace:'Prediction',key:'max_order_apt_per_market',type:'SCALAR',
     current_release:'V1.0.0',current_active:'2024-01-01 00:00',value:'50000',
     candidate:null,status:'active'},
    {namespace:'OTC',key:'max_order_apt',type:'SCALAR',
     current_release:'V1.1.0',current_active:'2024-03-01 00:00',value:'20000',
     candidate:{candidate_id:'C-024',editor:'param_editor_02',status:'pending_review',
       changes:'Increase to 50,000'},status:'active'},
    {namespace:'Risk',key:'large_order_threshold_apt',type:'SCALAR',
     current_release:'V1.2.0',current_active:'2024-04-15 00:00',value:'5000',
     candidate:null,status:'active'},
    {namespace:'Policy',key:'kyc_level_required_for_prediction',type:'SCALAR',
     current_release:'V1.0.0',current_active:'2024-01-01 00:00',value:'L1',
     candidate:null,status:'active'}
  ],

  releaseSnapshots: [
    {release_id:'REL-3.2.1',parameter:'daily_reward_coefficient',version:'3.2.1',
     status:'active',effective:'2024-06-10 08:30',diff:'Simplified 56-level table from 5-level buckets'},
    {release_id:'REL-3.2.0',parameter:'power_cap_by_robot_level',version:'3.2.0',
     status:'active',effective:'2024-06-01 00:00',diff:'Increased caps for Lv.20+'},
    {release_id:'REL-3.1.0',parameter:'robot_upgrade_cost_apt',version:'3.1.0',
     status:'active',effective:'2024-05-01 00:00',diff:'Reduced upgrade costs for Lv.1-20'},
    {release_id:'REL-3.0.0',parameter:'daily_reward_coefficient',version:'3.0.0',
     status:'archived',effective:'2024-04-01 00:00',diff:'Original 5-level buckets design'}
  ],

  /* ───── Support / Audit ───── */
  tickets: [
    {ticket_id:'TKT-034',user:'U-002/Maria Lopez',category:'kyc_help',priority:'medium',
     status:'waiting_user',subject:'KYC documents rejected — need guidance',
     created:'2024-06-09 14:00',last_reply:'2024-06-10 09:00',assignee:'cs_agent_01',sla:'24h'},
    {ticket_id:'TKT-033',user:'U-001/Alex Chen',category:'withdrawal',priority:'high',
     status:'in_progress',subject:'Withdrawal stuck in processing for 3 hours',
     created:'2024-06-10 06:00',last_reply:'2024-06-10 08:00',assignee:'cs_agent_02',sla:'4h'},
    {ticket_id:'TKT-032',user:'U-010/Emma Wilson',category:'robot_issue',priority:'medium',
     status:'resolved',subject:'Robot reward not showing in wallet',
     created:'2024-06-08 10:00',last_reply:'2024-06-08 14:00',assignee:'cs_agent_03',
     resolution:'ledger delay — posted within 1h'},
    {ticket_id:'TKT-031',user:'U-005/Sophie Martin',category:'account_access',priority:'critical',
     status:'in_progress',subject:'Account locked after password reset',
     created:'2024-06-10 03:00',last_reply:'2024-06-10 09:00',assignee:'cs_agent_02',sla:'2h'}
  ],

  auditLog: [
    {id:'AUD-1001',actor:'admin',action:'parameter_activate',target:'REL-3.2.1',
     result:'success',detail:'Activated daily_reward_coefficient V3.2.1',time:'2024-06-10 08:30',ip:'10.0.1.50'},
    {id:'AUD-1002',actor:'risk_approver_01',action:'case_approve',target:'RC-2024-053',
     result:'success',detail:'Account takeover case resolved — restricted U-003',time:'2024-06-05 12:05',ip:'10.0.1.52'},
    {id:'AUD-1003',actor:'finance_reviewer_01',action:'reconciliation_run',target:'Daily Recon',
     result:'matched',detail:'All pools reconciled with diff=0',time:'2024-06-10 08:00',ip:'10.0.1.55'},
    {id:'AUD-1004',actor:'param_editor_01',action:'candidate_create',target:'C-023',
     result:'success',detail:'Created candidate for power_cap_by_robot_level adjustment',time:'2024-06-09 18:00',ip:'10.0.1.51'},
    {id:'AUD-1005',actor:'ledger_operator_01',action:'reversal_submit',target:'APR-088',
     result:'pending',detail:'Submitted correction proposal for LE-004',time:'2024-06-09 16:20',ip:'10.0.1.54'},
    {id:'AUD-1006',actor:'admin',action:'role_change',target:'cs_agent_02',
     result:'success',detail:'Granted temp escalation access for critical tickets',time:'2024-06-10 09:00',ip:'10.0.1.50'}
  ],

  /* ───── User Restrictions ───── */
  userRestrictions: [
    {case_id:'RST-001',user:'U-003/田中太郎',type:'freeze_account',reason:'账户盗用',effective:'2024-06-05',expiry:'2024-06-19',status:'active'},
    {case_id:'RST-002',user:'U-006/박민수',type:'freeze_balance',reason:'异常交易',effective:'2024-03-15',expiry:'2024-09-15',status:'active'},
    {case_id:'RST-003',user:'U-002/Maria Lopez',type:'restrict_robot',reason:'冷却期',effective:'2024-06-01',expiry:'2024-06-15',status:'expired'}
  ],

  /* ───── RBAC Roles ───── */
  adminRoles: [
    {id:1,name:'Super Admin',desc:'Full system access',members:2,is_super:true},
    {id:2,name:'KYC Reviewer',desc:'Review KYC cases only',members:4},
    {id:3,name:'Finance Reviewer',desc:'Read all ledger, no write',members:2},
    {id:4,name:'Ledger Operator',desc:'Execute approved corrections',members:2},
    {id:5,name:'Risk Analyst',desc:'Create/analyze risk cases',members:3},
    {id:6,name:'Risk Approver',desc:'Approve risk dispositions',members:2},
    {id:7,name:'Parameter Editor',desc:'Edit drafts, cannot activate',members:2},
    {id:8,name:'Parameter Approver',desc:'Approve parameters, cannot edit',members:2},
    {id:9,name:'Release Operator',desc:'Activate releases, cannot edit',members:1},
    {id:10,name:'Auditor',desc:'Read all audits, no execute',members:1},
    {id:11,name:'Support Agent',desc:'Handle tickets, read user data',members:5},
    {id:12,name:'Security Admin',desc:'Manage RBAC, cannot access business',members:1}
  ],

  adminUsers: [
    {id:1,account:'admin',name:'System Admin',role:'Super Admin',status:'active',login_cnt:428,last_login:'2024-06-10 09:00'},
    {id:2,account:'kyc_reviewer',name:'Alice KYC',role:'KYC Reviewer',status:'active',login_cnt:156,last_login:'2024-06-10 08:00'},
    {id:3,account:'risk_analyst',name:'Bob Risk',role:'Risk Analyst',status:'active',login_cnt:89,last_login:'2024-06-10 07:30'},
    {id:4,account:'cs_agent_01',name:'Carol Support',role:'Support Agent',status:'active',login_cnt:234,last_login:'2024-06-10 09:30'},
    {id:5,account:'param_editor',name:'Dave Param',role:'Parameter Editor',status:'active',login_cnt:45,last_login:'2024-06-09 18:00'},
    {id:6,account:'auditor_01',name:'Eve Audit',role:'Auditor',status:'active',login_cnt:67,last_login:'2024-06-10 06:00'},
    {id:7,account:'finance_reviewer',name:'Frank Finance',role:'Finance Reviewer',status:'active',login_cnt:120,last_login:'2024-06-10 08:00'}
  ],

  /* ───── System Config ───── */
  siteConfig: [
    {key:'site.name',value:'Gainode',descr:'Platform name'},
    {key:'site.environment',value:'production',descr:'Runtime environment'},
    {key:'robot.default_rule_version',value:'V3.2.1',descr:'Default robot rule version'},
    {key:'prediction.p0_template',value:'FOOTBALL_PREMATCH_1X2',descr:'P0 prediction template'},
    {key:'otc.max_order_apt',value:'20000',descr:'OTC max order APT'},
    {key:'kyc.default_required_level',value:'L1',descr:'Default KYC level required'},
    {key:'support.auto_close_hours',value:'72',descr:'Auto-close resolved tickets after hours'},
    {key:'audit.retention_days',value:'365',descr:'Audit log retention days'}
  ],

  /* ───── Languages ───── */
  languages: [
    {code:'zh-CN',name:'简体中文',flag:'🇨🇳',status:'active',coverage:'100%',translations:2840},
    {code:'en-US',name:'English',flag:'🇺🇸',status:'active',coverage:'100%',translations:2840},
    {code:'ja-JP',name:'日本語',flag:'🇯🇵',status:'active',coverage:'92%',translations:2613},
    {code:'ko-KR',name:'한국어',flag:'🇰🇷',status:'active',coverage:'88%',translations:2499},
    {code:'th-TH',name:'ไทย',flag:'🇹🇭',status:'active',coverage:'78%',translations:2215},
    {code:'de-DE',name:'Deutsch',flag:'🇩🇪',status:'partial',coverage:'65%',translations:1846},
    {code:'fr-FR',name:'Français',flag:'🇫🇷',status:'partial',coverage:'60%',translations:1704}
  ],

  /* ───── Settlement Batches ───── */
  settlementBatches: [
    {batch_id:'SET-001',market:'MKT-003 FCB vs RMD',result:'HOME',status:'completed',
     orders_total:1203,win_orders:482,total_payout:'285,000',fee:'8,550',
     journal_batch:'B-890',settled:'2024-06-09 08:00',reconciled:true,recon_diff:0},
    {batch_id:'SET-002',market:'MKT-006 BAY vs BVB',result:'VOID',status:'completed',
     orders_total:0,win_orders:0,total_payout:'0',fee:'0',
     journal_batch:'B-886',settled:'2024-06-08 20:00',reconciled:true,recon_diff:0}
  ],

  /* ───── Refund / Correction ───── */
  refundCorrections: [
    {case_id:'REF-001',market:'MKT-006 BAY vs BVB',type:'refund',
     reason:'Event cancelled — void all orders',status:'executed',
     affected_orders:452,principal_apt:'87,500',fee_refund:'2,625',
     created:'2024-06-08 19:00',executed:'2024-06-08 20:00'},
    {case_id:'COR-001',market:'MKT-003 FCB vs RMD',type:'correction',
     reason:'Score revision: DRAW→HOME 2-1',status:'pending_approval',
     affected_orders:1203,old_result:'DRAW',new_result:'HOME',
     impact_apt:'±45,000',created:'2024-06-09 06:00'}
  ],

  /* ───── Emergency Actions ───── */
  emergencyActions: [
    {action_id:'EMG-001',type:'market_suspend',target:'MKT-002 LFC vs MNU',
     reason:'Suspected match fixing',status:'executed',
     executor:'admin',approver:'risk_approver_01',
     executed:'2024-06-10 07:30',post_review_status:'pending',post_deadline:'2024-06-17'},
    {action_id:'EMG-002',type:'user_suspend',target:'U-003',
     reason:'Account takeover — freeze all',status:'executed',
     executor:'admin',approver:'security_admin',
     executed:'2024-06-05 12:00',post_review_status:'completed',post_deadline:'2024-06-12'},
    {action_id:'EMG-003',type:'parameter_rollback',target:'REL-3.2.1',
     reason:'Reward spike detected',status:'pending_confirmation',
     executor:'release_operator_01',created:'2024-06-10 10:00'}
  ],

  /* ───── Async Jobs ───── */
  asyncJobs: [
    {job_id:'JOB-056',type:'settlement_calculation',target:'MKT-002 LFC vs MNU',
     status:'scheduled',scheduled:'2024-06-12 17:35',retries:0},
    {job_id:'JOB-055',type:'reward_batch_posting',target:'Batch B-892',
     status:'completed',records:1234,success:1232,failed:2,dlq:true,
     started:'2024-06-10 06:05',completed:'2024-06-10 06:30'},
    {job_id:'JOB-054',type:'reconciliation_daily',target:'All pools',
     status:'completed',diff:0,started:'2024-06-10 08:00',completed:'2024-06-10 08:15'},
    {job_id:'JOB-053',type:'ledger_reversal',target:'APR-088 / LE-004',
     status:'running',progress:'72%',started:'2024-06-09 16:31'},
    {job_id:'JOB-052',type:'data_export',target:'Audit Q2 2024',
     status:'failed',error:'Disk space exceeded',retries:3}
  ],

  /* ───── Ticket Detail (TKT-033) ───── */
  ticketConversations: {
    'TKT-031':{ticket_id:'TKT-031',user:'Sophie Martin',
      timeline:[{type:'user',actor:'Sophie Martin',msg:'My account was locked after the password reset. I cannot access my funds.',time:'03:00',visible:'user'},{type:'agent',actor:'cs_agent_02',msg:'Hi Sophie, we detected the lock was triggered by the password reset flow. We are verifying your identity now.',time:'03:30',visible:'user'},{type:'internal',actor:'cs_agent_02',msg:'Account lock confirmed — password reset triggered MFA failure count. Escalating for manual identity review.',time:'03:45',visible:'internal'},{type:'agent',actor:'cs_agent_02',msg:'We have initiated an identity verification review. This may take up to 2 hours. We will notify you once access is restored.',time:'04:00',visible:'user'}],
      related:[{type:'user',id:'U-005',label:'Sophie Martin — User 360'}]
    },
    'TKT-032':{ticket_id:'TKT-032',user:'Emma Wilson',
      timeline:[{type:'user',actor:'Emma Wilson',msg:'My robot reward for the last cycle is not showing in my wallet. The robot status says claimed but nothing appeared.',time:'10:00',visible:'user'},{type:'agent',actor:'cs_agent_03',msg:'Hi Emma, we checked the reward batch. There was a ledger posting delay — the entry was queued but not yet committed. We are re-triggering the posting.',time:'10:30',visible:'user'},{type:'internal',actor:'cs_agent_03',msg:'Ledger batch L-334 delayed. Reward posted but not committed. Re-trigger confirmed.',time:'10:35',visible:'internal'},{type:'agent',actor:'cs_agent_03',msg:'The reward has been posted. You should see 120 APT in your wallet now. Please refresh and confirm.',time:'11:00',visible:'user'},{type:'user',actor:'Emma Wilson',msg:'Yes, I can see it now. Thank you!',time:'11:15',visible:'user'}],
      related:[{type:'user',id:'U-010',label:'Emma Wilson — User 360'},{type:'robot',id:'RB-005',label:'Emma\'s Robot RB-005'},{type:'ledger',id:'L-334',label:'Reward batch L-334'}]
    },
    'TKT-033':{ticket_id:'TKT-033',user:'U-001 / Alex Chen',
    timeline:[
      {type:'user',actor:'Alex Chen',msg:'My withdrawal of 5,000 APT has been processing for 3 hours. Is there an issue?',time:'06:00',visible:'user'},
      {type:'system',msg:'Auto-assigned to cs_agent_02 (SLA: 4h)',time:'06:01'},
      {type:'internal',actor:'cs_agent_02',msg:'Checked — batch B-891 delayed by reconciliation. ETA 15 min.',time:'06:30',visible:'internal'},
      {type:'agent',actor:'cs_agent_02',msg:'Hi Alex, we identified the cause. Our batch processing had a brief delay during daily reconciliation. The transfer will complete within 15 minutes.',time:'06:35',visible:'user'},
      {type:'user',actor:'Alex Chen',msg:'Thank you for the update. Is there any compensation?',time:'06:40',visible:'user'},
      {type:'internal',actor:'cs_agent_02',msg:'User requesting compensation. Standard policy: delay < 24h, no automatic compensation.',time:'06:42',visible:'internal'},
      {type:'agent',actor:'cs_agent_02',msg:'Our policy only provides compensation for delays exceeding 24 hours. Your withdrawal should complete shortly.',time:'06:45',visible:'user'},
      {type:'user',actor:'Alex Chen',msg:'Understood, I\'ll wait.',time:'06:50',visible:'user'},
      {type:'agent',actor:'cs_agent_02',msg:'Transfer completed at 07:15. 5,000 APT should now appear in your external wallet.',time:'07:20',visible:'user'}
    ],
    related:[{type:'ledger',id:'LE-002',label:'Withdrawal -5,000 APT'},{type:'batch',id:'B-891',label:'Journal batch B-891'},{type:'user',id:'U-001',label:'Alex Chen — User 360'}]
    },
    'TKT-034':{ticket_id:'TKT-034',user:'Maria Lopez',
      timeline:[{type:'user',actor:'Maria Lopez',msg:'I submitted my KYC documents three times and they keep getting rejected. I need help.',time:'11:20',visible:'user'},{type:'agent',actor:'cs_agent_02',msg:'Hi Maria, let me check your submissions. Please hold.',time:'11:45',visible:'user'}],
      related:[{type:'user',id:'U-002',label:'Maria Lopez — User 360'}]
    }
  }
};
