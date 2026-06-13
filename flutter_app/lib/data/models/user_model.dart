class UserModel {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String? avatar;
  final String role;
  final double balance;
  final String lang;
  final bool isVerified;

  const UserModel({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    this.avatar,
    required this.role,
    required this.balance,
    required this.lang,
    required this.isVerified,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      name: json['name'] ?? '',
      phone: json['phone'] ?? '',
      email: json['email'],
      avatar: json['avatar'],
      role: json['role'] ?? 'user',
      balance: (json['balance'] ?? 0).toDouble(),
      lang: json['lang'] ?? 'uz',
      isVerified: json['is_verified'] ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'email': email,
        'avatar': avatar,
        'role': role,
        'balance': balance,
        'lang': lang,
        'is_verified': isVerified,
      };

  UserModel copyWith({
    String? name,
    String? email,
    String? avatar,
    double? balance,
    String? lang,
  }) =>
      UserModel(
        id: id,
        name: name ?? this.name,
        phone: phone,
        email: email ?? this.email,
        avatar: avatar ?? this.avatar,
        role: role,
        balance: balance ?? this.balance,
        lang: lang ?? this.lang,
        isVerified: isVerified,
      );
}
