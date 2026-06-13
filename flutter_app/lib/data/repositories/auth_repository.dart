import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/user_model.dart';
import '../../core/network/api_client.dart';
import '../../core/constants/app_constants.dart';

class AuthRepository {
  final ApiClient _api;
  final FlutterSecureStorage _storage;

  AuthRepository(this._api, this._storage);

  Future<Map<String, dynamic>> sendOtp(String phone, String type) async {
    final response = await _api.post('/auth/send-otp', data: {
      'phone': phone,
      'type': type,
    });
    return response.data;
  }

  Future<Map<String, dynamic>> verifyOtp(
      String phone, String code, String type) async {
    final response = await _api.post('/auth/verify-otp', data: {
      'phone': phone,
      'code': code,
      'type': type,
    });
    return response.data;
  }

  Future<UserModel> register({
    required String name,
    required String phone,
    required String password,
    required String code,
  }) async {
    final response = await _api.post('/auth/register', data: {
      'name': name,
      'phone': phone,
      'password': password,
      'password_confirmation': password,
      'code': code,
    });

    final token = response.data['token'];
    final user = UserModel.fromJson(response.data['user']);
    await _saveSession(token, user);
    return user;
  }

  Future<UserModel> login(String phone, String password) async {
    final response = await _api.post('/auth/login', data: {
      'phone': phone,
      'password': password,
    });

    final token = response.data['token'];
    final user = UserModel.fromJson(response.data['user']);
    await _saveSession(token, user);
    return user;
  }

  Future<UserModel> loginWithOtp(String phone, String code) async {
    final response = await _api.post('/auth/login-otp', data: {
      'phone': phone,
      'code': code,
    });

    final token = response.data['token'];
    final user = UserModel.fromJson(response.data['user']);
    await _saveSession(token, user);
    return user;
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } catch (_) {}
    await _clearSession();
  }

  Future<UserModel?> getCurrentUser() async {
    final userJson = await _storage.read(key: AppConstants.userKey);
    if (userJson == null) return null;
    return UserModel.fromJson(jsonDecode(userJson));
  }

  Future<String?> getToken() async {
    return await _storage.read(key: AppConstants.tokenKey);
  }

  Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null;
  }

  Future<void> _saveSession(String token, UserModel user) async {
    await _storage.write(key: AppConstants.tokenKey, value: token);
    await _storage.write(
        key: AppConstants.userKey, value: jsonEncode(user.toJson()));
  }

  Future<void> _clearSession() async {
    await _storage.delete(key: AppConstants.tokenKey);
    await _storage.delete(key: AppConstants.userKey);
  }
}
