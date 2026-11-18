# SilverShop Pay Plus (`silvershop-pay-plus`)

> 增强型 SilverStripe 电商与多网关结账系统 / Enhanced SilverStripe Ecommerce & Multi-Gateway Checkout Framework.

---

## 简体中文

### 简介
`silvershop-pay-plus` 是基于成熟 SilverStripe 电商框架 `silvershop/silvershop-core` 进行深度二次开发的增强版本。本项目重点针对已知缺陷、多主流支付网关适配（Stripe、PayPal、Mollie 等）、结账表单验证、全生命周期退款与审计追踪，以及金额计算精度进行了系统级重构与加固。

### 功能与改进点（对应 CHANGES.md）

1. **修复结账支付组件已知 Bug 与空安全/表单验证缺陷**：
   - 修正 `Payment::getRequiredFields()` 中多网关存在时的必填校验逻辑反转，确保多渠道场景下顾客必须显式选择支付方式。
   - 修复 `Payment::getFormFields()` 与 `CheckoutFieldFactory` 中把数组当做标量传给 `OptionsetField` 默认值的缺陷，规范选中值绑定。
   - 在 `OrderProcessor::getGatewayData()` 中增加空安全回退，彻底解决无收发货地址（如虚拟物品、数字服务）场景下访问 `$billing->Company` 或 `$shipping->Address` 导致的 PHP 8 致命异常。
   - 增强 `OrderProcessor::placeOrder()` 在无 HTTP 控制器上下文（如 CLI 脚本、异步队列 Worker、单元测试）下的环境回退与 IP 记录。
   - 在 `OnsitePayment` 中增加信用卡数据格式清洗，去除空格与连字符后再执行 Luhn 校验，防止空键与格式误判。

2. **主流支付网关注册体系与元数据管理**：
   - 新增 `PaymentGatewayRegistry` 核心服务，集中注册与管理主流网关（Stripe、PayPal Express / REST、Mollie、银行转账/Manual、测试 Dummy）。
   - 提供标准化网关元数据接口（显示名、图标类名、徽标标签、支持币种与跳转模式），使结账模板可直接优雅渲染网关图标。
   - 新增 `_config/gateways.yml` 配置模板，简化 Omnipay 参数绑定与环境变量对接。
   - 在 `Checkout::getSelectedPaymentMethod()` 中引入失效/下线网关的自动检测与安全重置。

3. **补齐支付全生命周期事件与退款/撤销审计状态流转**：
   - 在 `PaymentExtension` 中新增 `onRefunded` 事件回调，全额退款时将订单状态流转至 `Refunded`，部分退款时记录 `payment_partial_refund`。
   - 补齐 `onVoid` 与 `onCancelled` 生命周期钩子，分别记录 `payment_voided` 与 `payment_cancelled` 结构化审计流转。
   - 在 `Order` 模型新增 `TotalRefunded()` 方法，便于后台及报表精准核算退款净额。
   - 修正下单金额状态判定，避免因浮点精度或微额多付被误标为 `Unpaid`。

4. **结账金额计算与多币种转换精度优化**：
   - 增强 `OrderTotalCalculator`，强制应用配置的 `rounding_precision`（默认 2 位），避免多税率/运费叠加时的浮点累积漂移；增加日志服务空安全调用。
   - `SimpleCurrencyService::convert()` 增加可选精度控制参数，消除跨币种运算产生的超长微额小数。
   - 完善零元订单（`allow_zero_order_total` 且 `GrandTotal() == 0`）在 `Order::canPay()` 中的结账放行逻辑。

### 快速开始

#### 环境要求
- PHP ^8.3
- SilverStripe CMS ^6.0
- MySQL / MariaDB 10.11+

#### 1. 安装
在 SilverStripe 项目中通过 Composer 引入：
```bash
composer require silvershop/core
```

#### 2. 配置支付网关
在项目的 `app/_config/gateways.yml` 中配置所需启用的支付网关（例如 Stripe、PayPal 或 Manual）：
```yaml
SilverStripe\Omnipay\Model\Payment:
  allowed_gateways:
    - Stripe
    - PayPal_Express
    - Manual

SilverStripe\Omnipay\GatewayInfo:
  Stripe:
    use_authorize: false
    is_offsite: true
    parameters:
      apiKey: '`STRIPE_API_KEY`'
      publishableKey: '`STRIPE_PUBLISHABLE_KEY`'
```

#### 3. 运行构建与测试数据任务
在浏览器或终端执行数据库构建：
```bash
# 浏览器访问：http://yoursite.local/dev/build?flush=1
# 或终端运行：
vendor/bin/sake dev/build "flush=1"
```

测试环境可运行自带填充任务：
```bash
vendor/bin/sake dev/tasks/PopulateShopTask
```

#### 4. 运行自测
```bash
composer lint
composer test
```

### 来源与致谢
本项目基于 [silvershop-core](https://github.com/silvershop/silvershop-core) 二次开发，原项目由 silvershop 以 BSD-2-Clause 许可证开源。

### 许可证
沿用原许可证，全文见根目录 [LICENSE](LICENSE)。

---

## English

### Introduction
`silvershop-pay-plus` is an enhanced distribution of `silvershop/silvershop-core`, the premier ecommerce framework for SilverStripe CMS. This project delivers substantive enhancements focused on critical bug fixes, mainstream payment gateway integration (Stripe, PayPal, Mollie), checkout validation hardening, full-lifecycle refund & void audit trails, and multi-currency calculation precision.

### Key Features & Improvements (from CHANGES.md)

1. **Checkout & Payment Component Bug Fixes & Null-Safety**:
   - Inverted and fixed required-field logic in `Payment::getRequiredFields()`: `PaymentMethod` is now strictly required when multiple gateways are available.
   - Fixed `OptionsetField` initialization in `Payment::getFormFields()` and `CheckoutFieldFactory` by passing the scalar selected key instead of an array of all gateway keys.
   - Added null-safe property access in `OrderProcessor::getGatewayData()` to prevent fatal PHP 8 errors when handling orders without shipping or billing addresses (e.g. virtual products or digital services).
   - Added safe fallback for client IP resolution in `OrderProcessor::placeOrder()` when invoked outside an HTTP controller context (e.g. CLI commands, queue workers, unit tests).
   - Sanitized credit card inputs in `OnsitePayment::validateData()` before running Luhn validation, avoiding undefined index warnings and format validation failures.

2. **Mainstream Payment Gateway Registry & Metadata Management**:
   - Introduced `PaymentGatewayRegistry` to centrally manage mainstream gateways (Stripe, PayPal Express/REST, Mollie, Manual/Bank Transfer, Dummy).
   - Provided rich metadata (titles, icons, badge labels, supported currencies, gateway type) for streamlined checkout UI rendering.
   - Added `_config/gateways.yml` configuration template for standard Omnipay environment bindings.
   - Sanitized stored session gateway selections in `Checkout::getSelectedPaymentMethod()`, automatically resetting stale or disabled gateways.

3. **Full Lifecycle Payment Events & Refund/Void Audit Trails**:
   - Implemented `onRefunded` in `PaymentExtension`: transitions order status to `Refunded` upon full refund and logs a structured `OrderTransition` (`payment_refunded`).
   - Implemented `onVoid` and `onCancelled` lifecycle hooks to track authorization voids and payment cancellations in the audit trail.
   - Added `TotalRefunded()` calculation method to the `Order` model for accurate financial reporting.
   - Optimized `OrderProcessor::placeOrder()` status evaluation to use a threshold check instead of strict float comparison (`!== 0.0`), preventing erroneous `Unpaid` statuses due to micro-discrepancies.

4. **Order Total Calculation & Currency Precision Optimization**:
   - Enforced `rounding_precision` directly in `OrderTotalCalculator::calculate()` to prevent fractional cumulative rounding drift across multi-step modifiers; added null-safe logging.
   - Added optional precision parameter to `SimpleCurrencyService::convert()` to eliminate long floating-point artifacts during cross-currency checkout.
   - Streamlined zero-dollar order checkout in `Order::canPay()` when `allow_zero_order_total` is enabled.

### Quick Start

#### Requirements
- PHP ^8.3
- SilverStripe CMS ^6.0
- MySQL / MariaDB 10.11+

#### 1. Installation
Install into your SilverStripe project via Composer:
```bash
composer require silvershop/core
```

#### 2. Configuration
Configure payment gateways in your project's `app/_config/gateways.yml`:
```yaml
SilverStripe\Omnipay\Model\Payment:
  allowed_gateways:
    - Stripe
    - PayPal_Express
    - Manual

SilverStripe\Omnipay\GatewayInfo:
  Stripe:
    use_authorize: false
    is_offsite: true
    parameters:
      apiKey: '`STRIPE_API_KEY`'
      publishableKey: '`STRIPE_PUBLISHABLE_KEY`'
```

#### 3. Build Database & Populate Demo Data
Run database rebuild via sake CLI or browser:
```bash
vendor/bin/sake dev/build "flush=1"
```

To populate demo shop data:
```bash
vendor/bin/sake dev/tasks/PopulateShopTask
```

#### 4. Running Tests
```bash
composer lint
composer test
```

### Sources & Acknowledgements
This project is a rework based on [silvershop-core](https://github.com/silvershop/silvershop-core), originally open-sourced by silvershop under the BSD-2-Clause license.

### License
Released under the original BSD-2-Clause license. See [LICENSE](LICENSE) for full details.
