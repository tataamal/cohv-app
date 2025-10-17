# @app.route('/api/data_refresh', methods=['GET'])
# def sap_refresh():
#     """
#     GET  : /api/data_refresh?plant=A100&AUFNR=000000123456&AUFNR=000000123457
#            atau /api/data_refresh?plant=A100&AUFNR=000000123456,000000123457
#     POST : body {"plant":"A100","AUFNR":["000000123456","000000123457"]}
#     """
#     try:
#         # --- ambil input ---
#         if request.method == 'GET':
#             plant = request.args.get('plant') or request.args.get('WERKS')
#             aufnr_list = request.args.getlist('AUFNR')
#             if not aufnr_list:
#                 s = (request.args.get('AUFNR') or '').strip()
#                 if s:
#                     aufnr_list = [x.strip() for x in s.split(',') if x.strip()]
#         else:  # POST
#             data = request.get_json(silent=True) or {}
#             plant = data.get('plant') or data.get('WERKS')
#             tmp = data.get('AUFNR') or data.get('orders') or []
#             if isinstance(tmp, list):
#                 aufnr_list = [str(x).strip() for x in tmp if str(x).strip()]
#             elif isinstance(tmp, str):
#                 aufnr_list = [x.strip() for x in tmp.split(',') if x.strip()]
#             else:
#                 aufnr_list = []

#         if not plant:
#             return jsonify({'error': 'Missing plant parameter'}), 400

#         # helper: pad AUFNR ke 12 digit
#         def pad12(v: str) -> str:
#             v = str(v or '')
#             return v if len(v) >= 12 else v.zfill(12)

#         username, password = get_credentials()
#         conn = connect_sap(username, password)

#         # --- SINGLE (atau tanpa AUFNR, tergantung RFC: berarti semua di plant) ---
#         if not aufnr_list or len(aufnr_list) == 1:
#             a = pad12(aufnr_list[0]) if aufnr_list else None
#             res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, AUFNR=a)
#             return jsonify({
#                 "plant": plant,
#                 "AUFNR": a,
#                 "T_DATA": res.get('T_DATA', []),
#                 "T_DATA1": res.get('T_DATA1', []),
#                 "T_DATA2": res.get('T_DATA2', []),
#                 "T_DATA3": res.get('T_DATA3', []),
#                 "T_DATA4": res.get('T_DATA4', []),
#             }), 200

#         # --- MULTI: loop per AUFNR dalam 1 koneksi SAP ---
#         out = []
#         for a in aufnr_list:
#             a12 = pad12(a)
#             res = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, AUFNR=a12)
#             out.append({
#                 "plant": plant,
#                 "AUFNR": a12,
#                 "T_DATA": res.get('T_DATA', []),
#                 "T_DATA1": res.get('T_DATA1', []),
#                 "T_DATA2": res.get('T_DATA2', []),
#                 "T_DATA3": res.get('T_DATA3', []),
#                 "T_DATA4": res.get('T_DATA4', []),
#             })

#         return jsonify({"results": out}), 200

#     except ValueError as ve:
#         return jsonify({'error': str(ve)}), 401
#     except Exception as e:
#         print("[COHV] data_refresh exception:", repr(e))
#         return jsonify({'error': str(e)}), 500

# @app.route('/api/sap_combined_multi', methods=['POST'])
# def sap_combined_multi():
#     data = request.get_json()
#     plant = data.get('plant')
#     aufnrs = data.get('aufnrs', [])

#     if not plant or not isinstance(aufnrs, list):
#         return jsonify({'error': 'Missing plant or aufnrs[]'}), 400

#     try:
#         username, password = get_credentials()
#         conn = connect_sap(username, password)
#         import time
#         time.sleep(2)
#         all_data1 = []
#         all_data4 = []

#         for aufnr in aufnrs:
#             result = conn.call('Z_FM_YPPR074Z', P_WERKS=plant, P_AUFNR=aufnr)
#             all_data1.extend(result.get('T_DATA1', []))
#             all_data4.extend(result.get('T_DATA4', []))

#         return jsonify({
#             'T_DATA1': all_data1,
#             'T_DATA4': all_data4,
#         })

#     except ValueError as ve:
#         return jsonify({'error': str(ve)}), 401
#     except Exception as e:
#         return jsonify({'error': str(e)}), 500

# RELEASE PRO
# @app.route('/api/release_order', methods=['POST'])
# def release_order():
#     try:
#         username, password = get_credentials()
#         conn = connect_sap(username, password)

#         data = request.get_json()
#         print("Data diterima untuk release:", data)

#         aufnr = data.get('AUFNR')
#         if not aufnr:
#             return jsonify({'error': 'AUFNR is required'}), 400

#         print("Calling RFC BAPI_PRODORD_RELEASE...")

#         result = conn.call(
#             'BAPI_PRODORD_RELEASE',
#             RELEASE_CONTROL='1',
#             WORK_PROCESS_GROUP='COWORK_BAPI',
#             WORK_PROCESS_MAX=99,
#             ORDERS=[{'ORDER_NUMBER': aufnr}]
#         )

#         return jsonify(result)

#     except ValueError as ve:
#         return jsonify({'error': str(ve)}), 401
#     except Exception as e:
#         print("Exception saat release order:", str(e))
#         return jsonify({'error': str(e)}), 500

# CONVERT 
# @app.route('/api/create_prod_order', methods=['POST'])
# def create_prod_order_from_plord():
#     try:
#         username, password = get_credentials()
#         conn = connect_sap(username, password)

#         data = request.get_json()
#         plnum = data.get('PLANNED_ORDER')
#         order_type = data.get('AUART')
#         plant = data.get('PLANT')

#         if not plnum or not order_type:
#             return jsonify({'error': 'PLANNED_ORDER and AUART are required'}), 400

#         print(f"Calling BAPI_PRODORD_CREATE_FROM_PLORD with PLANNED_ORDER: {plnum} and ORDER_TYPE: {order_type}")

#         result = conn.call(
#             'BAPI_PRODORD_CREATE_FROM_PLORD',
#             PLANNED_ORDER=plnum,
#             ORDER_TYPE=order_type
#         )

#         return_data = result.get('RETURN', {})
#         order_number = result.get('PRODUCTION_ORDER', '')

#         print("Result from BAPI_PRODORD_CREATE_FROM_PLORD:", result)

#         # Commit jika tidak error
#         if return_data.get('TYPE') != 'E':
#             conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')

#         return jsonify({
#             'success': return_data.get('TYPE') != 'E',
#             'order_number': order_number,
#             'return': return_data
#         })

#     except ValueError as ve:
#         return jsonify({'error': str(ve)}), 401
#     except Exception as e:
#         print("Exception:", str(e))
#         return jsonify({'error': str(e)}), 500

# @app.route('/api/create_prod_order', methods=['POST'])
# def create_prod_order_from_plord():
#     try:
#         username, password = get_credentials()
#         conn = connect_sap(username, password)

#         data = request.get_json() or {}

#         # --- helper untuk normalisasi RETURN ---
#         def normalize_return(ret):
#             if isinstance(ret, list):
#                 msgs = ret
#             elif isinstance(ret, dict):
#                 msgs = [ret] if ret else []
#             else:
#                 msgs = []
#             has_error = any((m.get('TYPE') in ('E', 'A')) for m in msgs)
#             # minimal fields agar enak dipakai FE
#             msgs = [{
#                 'type':    m.get('TYPE'),
#                 'id':      m.get('ID'),
#                 'number':  m.get('NUMBER'),
#                 'message': m.get('MESSAGE'),
#                 'log_no':  m.get('LOG_NO'),
#                 'log_msg_no': m.get('LOG_MSG_NO')
#             } for m in msgs]
#             return msgs, has_error

#         # --- deteksi batch ---
#         items = data.get('ITEMS') or data.get('PLANNED_ORDERS')
#         if isinstance(items, list):
#             # Bisa list of dict atau list of string PLANNED_ORDER
#             def to_item(x):
#                 if isinstance(x, dict):
#                     return x
#                 return {
#                     'PLANNED_ORDER': x,
#                     'AUART': data.get('AUART'),
#                     'PLANT': data.get('PLANT')
#                 }

#             results = []
#             for it in map(to_item, items):
#                 plnum = it.get('PLANNED_ORDER')
#                 auart = it.get('AUART') or data.get('AUART')
#                 plant = it.get('PLANT') or data.get('PLANT')

#                 if not plnum or not auart:
#                     results.append({
#                         'planned_order': plnum,
#                         'plant': plant,
#                         'production_orders': [],
#                         'success': False,
#                         'messages': [{'type': 'E', 'message': 'PLANNED_ORDER and AUART are required'}]
#                     })
#                     continue

#                 print(f"[COHV] CREATE_FROM_PLORD: PLO={plnum} AUART={auart}")
#                 res = conn.call('BAPI_PRODORD_CREATE_FROM_PLORD',
#                                 PLANNED_ORDER=plnum,
#                                 ORDER_TYPE=auart)

#                 msgs, has_error = normalize_return(res.get('RETURN'))
#                 aufnr = (res.get('PRODUCTION_ORDER') or '').zfill(12)
#                 orders = [aufnr] if aufnr.strip('0') else []

#                 results.append({
#                     'planned_order': plnum,
#                     'plant': plant,
#                     'production_orders': orders,   # <- satu atau lebih (saat ini 1 per PLO)
#                     'success': not has_error,
#                     'messages': msgs
#                 })

#             return jsonify({'results': results}), 200

#         # --- single item ---
#         plnum = data.get('PLANNED_ORDER')
#         auart = data.get('AUART')
#         plant = data.get('PLANT')  # ini kita echo balik ke FE

#         if not plnum or not auart:
#             return jsonify({'error': 'PLANNED_ORDER and AUART are required'}), 400

#         print(f"[COHV] CREATE_FROM_PLORD: PLO={plnum} AUART={auart}")
#         result = conn.call('BAPI_PRODORD_CREATE_FROM_PLORD',
#                            PLANNED_ORDER=plnum,
#                            ORDER_TYPE=auart)

#         msgs, has_error = normalize_return(result.get('RETURN'))
#         aufnr = (result.get('PRODUCTION_ORDER') or '').zfill(12)
#         orders = [aufnr] if aufnr.strip('0') else []

#         return jsonify({
#             'planned_order': plnum,
#             'plant': plant,                 # <- dikembalikan sesuai request
#             'production_orders': orders,    # <- array; bisa 1 atau >1
#             'success': not has_error,
#             'messages': msgs
#         }), 200

#     except ValueError as ve:
#         return jsonify({'error': str(ve)}), 401
#     except Exception as e:
#         print("[COHV] Exception:", str(e))
#         return jsonify({'error': str(e)}), 500

# @app.route('/api/sap-po', methods=['POST'])
# def fetch_purchase_orders():
#     try:
#         username, password = get_credentials()
#         plants = request.json.get('plants', [])

#         all_data1 = []
#         all_data2 = []
#         lock = threading.Lock()

#         def fetch_from_sap(plant):
#             try:
#                 local_conn = connect_sap(username, password)
#                 print(f"Fetching from plant: {plant}")
#                 result = local_conn.call('Z_FM_YMMR068', P_WERKS=plant)

#                 with lock:
#                     if 'T_DATA1' in result:
#                         all_data1.extend(result['T_DATA1'])
#                     if 'T_DATA2' in result:
#                         # PASTIKAN TIDAK ADA MANIPULASI TEXT DI SINI - BIARKAN ORIGINAL
#                         for row in result['T_DATA2']:
#                             # JANGAN TAMBAHKAN APAPUN KE TEXT FIELD
#                             # Biarkan kosong jika memang kosong dari SAP
#                             pass
#                         all_data2.extend(result['T_DATA2'])
#             except Exception as e:
#                 print(f"[ERROR] Plant {plant}: {str(e)}")

#         with ThreadPoolExecutor(max_workers=min(5, len(plants))) as executor:
#             executor.map(fetch_from_sap, plants)

#         return jsonify({
#             'T_DATA1': all_data1,
#             'T_DATA2': all_data2,
#         })

#     except Exception as e:
#         print("Exception:", str(e))
#         return jsonify({'error': str(e)}), 500

# @app.route('/api/reject_po', methods=['POST'])
# def reject_po():
#     try:
#         username, password = get_credentials()
#         data = request.json or {}
#         ebeln = data.get('EBELN')

#         if not ebeln:
#             return jsonify({'error': 'Parameter EBELN wajib diisi'}), 400

#         print("EBELN diterima:", ebeln)  # Debug

#         conn = connect_sap(username, password)
#         result = conn.call('Z_PO_REJECT', I_EBELN=ebeln)

#         return jsonify({'status': 'success', 'result': result}), 200

#     except Exception as e:
#         print("[ERROR] Reject PO:", str(e))
#         return jsonify({'status': 'error', 'message': str(e)}), 500

# @app.route('/api/z_po_comment_update', methods=['POST'])
# def comment_update():
#     try:
#         username, password = get_credentials()
#         data = request.json or {}

#         ebeln = data.get('PURCHASEORDER')
#         comment = data.get('COMMENT_TEXT')

#         print(f"[DEBUG] Received Comment Update: EBELN={ebeln}, TEXT={comment}")

#         if not ebeln or not comment:
#             return jsonify({'status': 'error', 'message': 'PURCHASEORDER dan COMMENT_TEXT wajib diisi'}), 400

#         conn = connect_sap(username, password)

#         result = conn.call('Z_PO_COMMENT_UPDATE',
#             PURCHASEORDER=ebeln,
#             COMMENT_TEXT=comment,
#             TEXT_ID='F01',
#             TEXT_LANGU='EN',
#             HEADER_LEVEL='X',
#             ITEM_NUMBER='00000'
#         )

#         print("[DEBUG] SAP Response:", result)
#         return jsonify({'status': 'success', 'result': result}), 200

#     except Exception as e:
#         print("[ERROR] Z_PO_COMMENT_UPDATE:", str(e))
#         return jsonify({'status': 'error', 'message': str(e)}), 500



# @app.route('/api/z_po_release2', methods=['POST'])
# def z_po_release2():
#     try:
#         username, password = get_credentials()
#         conn = connect_sap(username, password)

#         data = request.get_json()
#         ebeln = data.get('EBELN')
#         rel_code = data.get('REL_CODE')

#         if not ebeln or not rel_code:
#             return jsonify({'status': 'error', 'message': 'EBELN and REL_CODE are required'}), 400

#         result = conn.call('Z_PO_RELEASE2', PURCHASEORDER=ebeln, PO_REL_CODE=rel_code)
#         return_table = result.get('RETURN', [])
#         first_return = return_table[0] if return_table else {}

#         if first_return.get('TYPE') != 'E':
#             conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
#             return jsonify({
#                 'status': 'success',
#                 'message': first_return.get('MESSAGE', ''),
#                 'details': return_table
#             }), 200
#         else:
#             return jsonify({
#                 'status': 'error',
#                 'message': first_return.get('MESSAGE', ''),
#                 'details': return_table
#             }), 200

#     except (ABAPApplicationError, ABAPRuntimeError, LogonError, CommunicationError) as sap_err:
#         return jsonify({
#             'status': 'sap_error',
#             'message': str(sap_err)
#         }), 200

#     except Exception as e:
#         print("Exception saat Z_PO_RELEASE2:", str(e))
#         return jsonify({'status': 'exception', 'error': str(e)}), 500