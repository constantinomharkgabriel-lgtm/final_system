import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart' as geocoding;
import 'package:latlong2/latlong.dart';

class LocationService {
  static const double caviteMinLat = 14.0;
  static const double caviteMaxLat = 14.7;
  static const double caviteMinLng = 120.5;
  static const double caviteMaxLng = 121.3;

  /// Check if coordinates are within Cavite service area
  static bool isWithinServiceArea(double latitude, double longitude) {
    return latitude >= caviteMinLat &&
        latitude <= caviteMaxLat &&
        longitude >= caviteMinLng &&
        longitude <= caviteMaxLng;
  }

  /// Request location permission
  static Future<bool> requestLocationPermission() async {
    final status = await Geolocator.requestPermission();
    return status == LocationPermission.always ||
        status == LocationPermission.whileInUse;
  }

  /// Get current device location
  static Future<LatLng?> getCurrentLocation() async {
    try {
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        final requested = await requestLocationPermission();
        if (!requested) return null;
      }

      if (permission == LocationPermission.deniedForever) {
        return null;
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 10),
      );

      return LatLng(position.latitude, position.longitude);
    } catch (e) {
      print('Error getting current location: $e');
      return null;
    }
  }

  /// Geocode address to coordinates using Nominatim (OpenStreetMap)
  /// Returns LatLng if found and within service area, null otherwise
  static Future<LatLng?> geocodeAddress({
    required String address,
    required String city,
    required String province,
  }) async {
    try {
      if (address.isEmpty || city.isEmpty) {
        return null;
      }

      final fullAddress = '$address, $city, $province, Philippines';

      // Use geocoding package which interfaces with Nominatim
      final placemarks = await geocoding.locationFromAddress(fullAddress);

      if (placemarks.isEmpty) {
        return null;
      }

      final location = placemarks.first;
      final lat = location.latitude;
      final lng = location.longitude;

      // Validate coordinates are within Cavite
      if (!isWithinServiceArea(lat, lng)) {
        throw Exception(
          'Location is outside our service area. Please select an address in Cavite Province.',
        );
      }

      return LatLng(lat, lng);
    } catch (e) {
      print('Error geocoding address: $e');
      rethrow;
    }
  }

  /// Reverse geocode coordinates to address (if needed)
  static Future<String?> reverseGeocode(double latitude, double longitude) async {
    try {
      final placemarks =
          await geocoding.placemarkFromCoordinates(latitude, longitude);

      if (placemarks.isEmpty) {
        return null;
      }

      final place = placemarks.first;
      return '${place.street}, ${place.locality}, ${place.administrativeArea}';
    } catch (e) {
      print('Error reverse geocoding: $e');
      return null;
    }
  }

  /// Calculate distance between two coordinates in kilometers
  static double calculateDistance(double lat1, double lon1, double lat2, double lon2) {
    const p = 0.017453292519943295;
    final c = cos((lat2 - lat1) * p / 2);
    final a = sin((lat2 - lat1) * p / 2) * sin((lat2 - lat1) * p / 2) +
        cos(lat1 * p) *
            cos(lat2 * p) *
            sin((lon2 - lon1) * p / 2) *
            sin((lon2 - lon1) * p / 2);
    return 12742 * atan2(sqrt(a), sqrt(1 - a));
  }

  static double sin(double x) => throw UnimplementedError();
  static double cos(double x) => throw UnimplementedError();
  static double atan2(double x, double y) => throw UnimplementedError();
  static double sqrt(double x) => throw UnimplementedError();
}
