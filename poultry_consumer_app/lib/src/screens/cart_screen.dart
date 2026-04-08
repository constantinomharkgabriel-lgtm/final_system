import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/cart_item.dart';
import '../models/consumer_session.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/location_service.dart';

typedef CartItemQuantityChange = void Function(Product product, {int quantity});

class CartScreen extends StatefulWidget {
  const CartScreen({
    super.key,
    required this.items,
    required this.session,
    required this.onAddItem,
    required this.onDecrementItem,
    required this.onRemoveItem,
    required this.onClearCart,
    required this.onOrderPlaced,
  });

  final List<CartItem> items;
  final ConsumerSession session;
  final CartItemQuantityChange onAddItem;
  final CartItemQuantityChange onDecrementItem;
  final ValueChanged<Product> onRemoveItem;
  final VoidCallback onClearCart;
  final VoidCallback onOrderPlaced;

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final _addressCtrl = TextEditingController();
  final _cityCtrl = TextEditingController();
  final _provinceCtrl = TextEditingController();
  final _postalCtrl = TextEditingController();
  
  bool _placing = false;
  bool _geocoding = false;
  String _paymentMethod = 'cod';
  
  LatLng? _selectedLocation; // Selected location from map
  MapController? _mapController;
  
  static const LatLng caviteCenter = LatLng(14.3577, 120.8854);
  static const double initialZoom = 12.0;

  @override
  void initState() {
    super.initState();
    _addressCtrl.text = widget.session.location ?? '';
    _provinceCtrl.text = 'Cavite';
    _mapController = MapController();
    
    // Request location permission on startup
    _requestLocationPermission();
  }

  @override
  void dispose() {
    _addressCtrl.dispose();
    _cityCtrl.dispose();
    _provinceCtrl.dispose();
    _postalCtrl.dispose();
    _mapController?.dispose();
    super.dispose();
  }

  Future<void> _requestLocationPermission() async {
    try {
      await LocationService.requestLocationPermission();
    } catch (e) {
      print('Location permission error: $e');
    }
  }

  Future<void> _useCurrentLocation() async {
    try {
      setState(() => _geocoding = true);
      
      final location = await LocationService.getCurrentLocation();
      if (location == null) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Unable to get current location')),
        );
        return;
      }

      // Update map and selected location
      _selectedLocation = location;
      _mapController?.move(location, initialZoom);

      // Reverse geocode to get address (optional)
      final address = await LocationService.reverseGeocode(
        location.latitude,
        location.longitude,
      );
      
      if (address != null && mounted) {
        _addressCtrl.text = address;
      }

      if (mounted) {
        setState(() {});
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: ${e.toString()}')),
      );
    } finally {
      if (mounted) {
        setState(() => _geocoding = false);
      }
    }
  }

  Future<void> _geocodeAddress() async {
    final address = _addressCtrl.text.trim();
    final city = _cityCtrl.text.trim();
    final province = _provinceCtrl.text.trim();

    if (address.isEmpty || city.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter address and city')),
      );
      return;
    }

    try {
      setState(() => _geocoding = true);

      final location = await LocationService.geocodeAddress(
        address: address,
        city: city,
        province: province,
      );

      if (location != null && mounted) {
        _selectedLocation = location;
        _mapController?.move(location, initialZoom);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Location found and centered on map')),
        );
        setState(() {});
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: ${e.toString()}')),
      );
    } finally {
      if (mounted) {
        setState(() => _geocoding = false);
      }
    }
  }

  Future<void> _placeOrder() async {
    if (widget.items.isEmpty) {
      return;
    }

    final address = _addressCtrl.text.trim();
    final city = _cityCtrl.text.trim();
    final province = _provinceCtrl.text.trim();
    final postalCode = _postalCtrl.text.trim();
    
    if (address.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Delivery address is required.')),
      );
      return;
    }

    if (_selectedLocation == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a location on the map.')),
      );
      return;
    }

    setState(() => _placing = true);
    try {
      const api = ApiService();
      final order = await api.placeOrder(
        token: widget.session.token,
        items: widget.items,
        deliveryAddress: address,
        deliveryCity: city,
        deliveryProvince: province,
        deliveryPostalCode: postalCode,
        paymentMethod: _paymentMethod,
        deliveryLatitude: _selectedLocation!.latitude,
        deliveryLongitude: _selectedLocation!.longitude,
      );

      if (!mounted) {
        return;
      }

      final payment = order['payment'];
      final checkoutUrl = payment is Map<String, dynamic>
          ? payment['checkout_url']?.toString()
          : null;

      widget.onOrderPlaced();
      _addressCtrl.clear();
      _cityCtrl.clear();
      _provinceCtrl.clear();
      _postalCtrl.clear();
      _selectedLocation = null;

      if (checkoutUrl != null && checkoutUrl.isNotEmpty) {
        final uri = Uri.tryParse(checkoutUrl);
        final launched = uri != null && await launchUrl(uri, mode: LaunchMode.externalApplication);

        if (!mounted) {
          return;
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              launched
                  ? 'Order created. Finish your $_paymentMethod payment in PayMongo.'
                  : 'Order created. Unable to auto-open PayMongo checkout.',
            ),
          ),
        );
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Order placed successfully.')),
      );
    } catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() => _placing = false);
      }
    }
  }

  int _nextChoiceQuantity(Product product, int current) {
    final options = product.normalizedOrderChoices;
    if (options.isEmpty) {
      return current + product.effectiveOrderStep;
    }

    for (final option in options) {
      if (option > current) {
        return option;
      }
    }

    return options.last;
  }

  int _previousChoiceQuantity(Product product, int current) {
    final options = product.normalizedOrderChoices;
    if (options.isEmpty) {
      return current - product.effectiveOrderStep;
    }

    for (final option in options.reversed) {
      if (option < current) {
        return option;
      }
    }

    return 0;
  }

  @override
  Widget build(BuildContext context) {
    final subtotal = widget.items.fold<double>(
      0,
      (sum, item) => sum + item.totalPrice,
    );
    final shipping = widget.items.isEmpty ? 0.0 : 100.0;
    final tax = subtotal * 0.12;
    final total = subtotal + shipping + tax;
    final itemCount = widget.items.fold<int>(0, (sum, item) => sum + item.quantity);

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Your Cart',
              style: Theme.of(context)
                  .textTheme
                  .headlineSmall
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            Text(
              '$itemCount item(s) selected',
              style: Theme.of(context)
                  .textTheme
                  .bodySmall
                  ?.copyWith(color: const Color(0xFF64748B)),
            ),
            const SizedBox(height: 14),
            Expanded(
              child: widget.items.isEmpty
                  ? const Center(
                      child: Text('No products yet. Add items from the Shop tab.'),
                    )
                  : ListView.separated(
                      itemCount: widget.items.length,
                      separatorBuilder: (context, index) =>
                          const SizedBox(height: 10),
                      itemBuilder: (context, index) {
                        final item = widget.items[index];
                        final quantityStep = item.product.effectiveOrderStep;
                        final hasChoiceList = item.product.hasCustomOrderChoices;
                        final nextChoiceQty = _nextChoiceQuantity(item.product, item.quantity);
                        final previousChoiceQty = _previousChoiceQuantity(item.product, item.quantity);
                        return Card(
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            item.product.name,
                                            style: Theme.of(context)
                                                .textTheme
                                                .titleSmall
                                                ?.copyWith(fontWeight: FontWeight.w800),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(item.product.farmName),
                                          const SizedBox(height: 6),
                                          Text(
                                            hasChoiceList
                                                ? 'Choices: ${item.product.normalizedOrderChoices.join(', ')} ${item.product.unit}'
                                                : (item.product.isBulkOrderEnabled
                                                    ? 'Bulk step: ${item.product.effectiveOrderStep} ${item.product.unit}'
                                                    : (item.product.effectiveMinimumOrder > 1
                                                        ? 'Minimum: ${item.product.effectiveMinimumOrder} ${item.product.unit}'
                                                        : 'Unit: ${item.product.unit}')),
                                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                                  color: const Color(0xFF94A3B8),
                                                  fontWeight: FontWeight.w600,
                                                ),
                                          ),
                                          const SizedBox(height: 6),
                                          Text(
                                            'PHP ${item.product.price.toStringAsFixed(2)} each',
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w700,
                                              color: Color(0xFFFB923C),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    IconButton(
                                      onPressed: () => widget.onRemoveItem(item.product),
                                      icon: const Icon(Icons.delete_outline),
                                      tooltip: 'Remove item',
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                Row(
                                  children: [
                                    IconButton.filledTonal(
                                      onPressed: () => widget.onDecrementItem(
                                        item.product,
                                        quantity: hasChoiceList ? previousChoiceQty : quantityStep,
                                      ),
                                      icon: const Icon(Icons.remove),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(horizontal: 12),
                                      child: Text(
                                        item.quantity.toString(),
                                        style: Theme.of(context)
                                            .textTheme
                                            .titleMedium
                                            ?.copyWith(fontWeight: FontWeight.w800),
                                      ),
                                    ),
                                    IconButton.filled(
                                      onPressed: item.quantity >= item.product.stock
                                          ? null
                                          : () => widget.onAddItem(
                                                item.product,
                                                quantity: hasChoiceList ? nextChoiceQty : quantityStep,
                                              ),
                                      icon: const Icon(Icons.add),
                                    ),
                                    const Spacer(),
                                    Text(
                                      'PHP ${item.totalPrice.toStringAsFixed(2)}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w800,
                                        color: Color(0xFF0F766E),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),
            const SizedBox(height: 12),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            'Checkout',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(fontWeight: FontWeight.w800),
                          ),
                          const Spacer(),
                          TextButton.icon(
                            onPressed: widget.items.isEmpty ? null : widget.onClearCart,
                            icon: const Icon(Icons.remove_shopping_cart_outlined),
                            label: const Text('Clear'),
                          ),
                        ],
                      ),
                      const Divider(),
                      
                      // Location Selection
                      Text(
                        'Delivery Location',
                        style: Theme.of(context)
                            .textTheme
                            .titleSmall
                            ?.copyWith(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 10),
                      
                      // Map Display
                      Container(
                        height: 200,
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: FlutterMap(
                          mapController: _mapController,
                          options: MapOptions(
                            center: _selectedLocation ?? caviteCenter,
                            zoom: initialZoom,
                            onTap: (tapPosition, latLng) {
                              if (LocationService.isWithinServiceArea(latLng.latitude, latLng.longitude)) {
                                setState(() => _selectedLocation = latLng);
                              } else {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Location is outside our service area (Cavite only)'),
                                  ),
                                );
                              }
                            },
                          ),
                          layers: [
                            TileLayerOptions(
                              urlTemplate: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                              subdomains: ['a', 'b', 'c'],
                              attributionBuilder: (_) {
                                return Text("© OpenStreetMap contributors");
                              },
                            ),
                            if (_selectedLocation != null)
                              MarkerLayerOptions(
                                markers: [
                                  Marker(
                                    width: 80.0,
                                    height: 80.0,
                                    point: _selectedLocation!,
                                    builder: (ctx) => const Icon(
                                      Icons.location_on,
                                      color: Colors.red,
                                      size: 40,
                                    ),
                                  ),
                                ],
                              ),
                          ],
                        ),
                      ),
                      
                      const SizedBox(height: 10),
                      
                      // Map Controls
                      Row(
                        children: [
                          Expanded(
                            child: FilledButton.icon(
                              onPressed: _geocoding ? null : _useCurrentLocation,
                              icon: const Icon(Icons.my_location),
                              label: Text(_geocoding ? 'Getting location...' : 'Use Current Location'),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: FilledButton.icon(
                              onPressed: _geocoding ? null : _geocodeAddress,
                              icon: const Icon(Icons.search),
                              label: Text(_geocoding ? 'Searching...' : 'Find Address'),
                            ),
                          ),
                        ],
                      ),
                      
                      const SizedBox(height: 14),
                      
                      // Selected Location Info
                      if (_selectedLocation != null)
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.blue.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '📍 Selected: ${_selectedLocation!.latitude.toStringAsFixed(4)}, ${_selectedLocation!.longitude.toStringAsFixed(4)}',
                            style: const TextStyle(fontSize: 12),
                          ),
                        ),
                      
                      const SizedBox(height: 14),
                      const Divider(),
                      
                      // Address Fields
                      TextField(
                        controller: _addressCtrl,
                        decoration: const InputDecoration(
                          labelText: 'Delivery Address',
                        ),
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _cityCtrl,
                              decoration: const InputDecoration(
                                labelText: 'City / Municipality',
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: TextField(
                              controller: _provinceCtrl,
                              decoration: const InputDecoration(
                                labelText: 'Province',
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _postalCtrl,
                        decoration: const InputDecoration(
                          labelText: 'Postal Code',
                        ),
                      ),
                      const SizedBox(height: 14),
                      Text(
                        'Payment Method',
                        style: Theme.of(context)
                            .textTheme
                            .titleSmall
                            ?.copyWith(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _paymentChip('cod', 'Cash on Delivery'),
                          _paymentChip('gcash', 'GCash via PayMongo'),
                          _paymentChip('paymaya', 'PayMaya via PayMongo'),
                        ],
                      ),
                      const SizedBox(height: 14),
                      _summaryRow('Subtotal', subtotal),
                      const SizedBox(height: 6),
                      _summaryRow('Shipping', shipping),
                      const SizedBox(height: 6),
                      _summaryRow('Tax', tax),
                      const Divider(height: 22),
                      _summaryRow('Total', total, emphasize: true),
                      const SizedBox(height: 10),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          style: FilledButton.styleFrom(
                            backgroundColor: const Color(0xFF0F766E),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                          onPressed: widget.items.isEmpty || _placing ? null : _placeOrder,
                          child: Text(
                            _placing
                                ? 'Placing Order...'
                                : _paymentMethod == 'cod'
                                    ? 'Place Order'
                                    : 'Proceed to PayMongo',
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _paymentChip(String value, String label) {
    final selected = _paymentMethod == value;
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => setState(() => _paymentMethod = value),
      selectedColor: const Color(0xFF0F766E),
      labelStyle: TextStyle(
        color: selected ? Colors.white : null,
        fontWeight: FontWeight.w700,
      ),
    );
  }

  Widget _summaryRow(String label, double amount, {bool emphasize = false}) {
    final style = emphasize
        ? const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)
        : const TextStyle(fontWeight: FontWeight.w600);

    return Row(
      children: [
        Text(label, style: style),
        const Spacer(),
        Text('PHP ${amount.toStringAsFixed(2)}', style: style),
      ],
    );
  }
}
