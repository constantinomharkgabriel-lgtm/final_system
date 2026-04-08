import 'dart:convert';
import 'dart:html' as html;
import '../models/consumer_session.dart';

class SessionStorageService {
  static const _sessionKey = 'consumer_session_v1';

  const SessionStorageService();

  Future<void> saveSession(ConsumerSession session) async {
    try {
      final value = jsonEncode(session.toJson());
      html.window.localStorage[_sessionKey] = value;
    } catch (_) {}
  }

  Future<ConsumerSession?> readSession() async {
    try {
      final raw = html.window.localStorage[_sessionKey];
      if (raw == null || raw.isEmpty) return null;
      final json = jsonDecode(raw);
      if (json is! Map<String, dynamic>) return null;
      return ConsumerSession.fromJson(json);
    } catch (_) {
      return null;
    }
  }

  Future<void> clearSession() async {
    try {
      html.window.localStorage.remove(_sessionKey);
    } catch (_) {}
  }
}
