# PHPUnit Test Implementation Guidelines for Endereco Shopware 6 Client

## Overview

These guidelines define the comprehensive standards for writing PHPUnit tests in the Endereco Shopware 6 Client project. The tests must use PHPUnit 12 features, focus on testing project-specific code, and maintain high readability and maintainability through consistent patterns and practices.

## Table of Contents

1. [Folder Structure](#folder-structure)
2. [Test Class Naming and Design](#test-class-naming-and-design)
3. [Test Method Naming and Documentation](#test-method-naming-and-documentation)
4. [Variable Naming Conventions](#variable-naming-conventions)
5. [Testing Strategies](#testing-strategies)
6. [Assertions](#assertions)
7. [Data Providers and Generators](#data-providers-and-generators)
8. [Stubs and Mocks](#stubs-and-mocks)
9. [Code Reuse Patterns](#code-reuse-patterns)
10. [Type Hints and Declarations](#type-hints-and-declarations)
11. [Code Formatting](#code-formatting)
12. [Method Organization](#method-organization)
13. [Anti-Patterns to Avoid](#anti-patterns-to-avoid)
14. [Preventing Redundant Tests](#preventing-redundant-tests)
15. [Inline Comments](#inline-comments)
16. [Test Quality Checklist](#test-quality-checklist)
17. [Running Unit Tests](#running-unit-tests)
18. [Code Coverage Analysis](#code-coverage-analysis)
19. [Complete Example](#complete-example)

## Folder Structure

The following structure is an example of how tests can be organized. Adapt it to match your project's architecture:

```
tests/
├── Unit/                           # Unit tests for isolated component testing
│   ├── Entity/                     # Entity-related tests
│   │   ├── CustomerAddress/        # Customer address entity tests
│   │   └── OrderAddress/           # Order address entity tests
│   ├── Service/                    # Service layer tests
│   ├── Subscriber/                 # Event subscriber tests
│   └── Validator/                  # Validator tests
├── Integration/                    # Integration tests with Shopware components
│   ├── Api/                        # API integration tests
│   ├── Database/                   # Database interaction tests
│   └── Plugin/                     # Plugin lifecycle tests
└── bootstrap.php                   # Test bootstrap file
```

## Test Class Naming and Design

### Naming Convention

- Test classes must end with `Test` suffix
- Test class name = `{ClassUnderTest}Test`
- Example: `EnderecoCustomerAddressExtensionEntity` → `EnderecoCustomerAddressExtensionEntityTest`

### Class Structure

```php
<?php declare(strict_types=1);

namespace Endereco\Shopware6Client\Tests\Unit\{ComponentPath};

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
// Other imports...

#[CoversClass({ClassUnderTest}::class)]
class {ClassUnderTest}Test extends TestCase
{
    // 1. Class properties (with type hints)
    private ServiceInterface&MockObject $serviceMock;
    private Context $context;
    
    // 2. setUp() method
    protected function setUp(): void
    {
        // initialization
    }
    
    // 3. tearDown() method (if needed)
    protected function tearDown(): void
    {
        // cleanup
    }
    
    // 4. Test methods (grouped by feature)
    
    // 5. Data provider methods (static)
    
    // 6. Private factory methods
    
    // 7. Private helper methods
}
```

### Required PHPUnit Attributes

- `#[CoversClass]`: Must be present on every test class
- `#[DataProvider]`: Use for parameterized tests
- Avoid `#[Group]` and `#[TestDox]` attributes

## Test Method Naming and Documentation

### Method Naming (RULE-MN-001)

- **Pattern**: `test{What}{Condition}{ExpectedResult}`
- **Requirements**: Start with 'test', use camelCase
- **Examples**:
  - ✅ `testCollectionCanAddManuallyCreatedExtensions`
  - ✅ `testGetUniqueIdentifierReturnsAddressId`
  - ✅ `testSyncDoesNotAffectUniqueIdentifier`
  - ✅ `testEnsureCreatesExtensionWhenNoneExists`
  - ❌ `testEnsure()` (too generic)
  - ❌ `test_ensure_creates_extension()` (wrong case)

### Method Documentation (RULE-DC-001)

Every test method must have a PHPDoc comment with:

```php
/**
 * Tests that {specific behavior} when {specific condition}.
 * 
 * {Additional context explaining WHY this test is important,
 * what business rule it validates, or what bug it prevents.
 * Keep it concise - 2-3 sentences maximum.}
 */
public function testMethodName(): void
{
    // Test implementation
}
```

Example:
```php
/**
 * Tests that the insurance creates and persists a new address extension
 * when the customer address entity doesn't have one attached.
 * 
 * This ensures data integrity for downstream services that rely on
 * the extension being present.
 */
public function testEnsureCreatesExtensionWhenNoneExists(): void
{
    // test implementation
}
```

## Variable Naming Conventions

### Mock and Stub Naming (RULE-VN-001)

- **Pattern**: `${serviceName}Mock` or `${serviceName}Stub`
- **Examples**:
  - ✅ `$addressCheckerMock`
  - ✅ `$customerRepositoryStub`
  - ❌ `$mockAddressChecker`
  - ❌ `$addressChecker` (for a mock)

### Test Fixture Naming (RULE-VN-002)

- **Pattern**: Use descriptive entity names
- **Examples**:
  - ✅ `$customerAddressEntity`
  - ✅ `$addressDto`
  - ✅ `$customerId`
  - ❌ `$address` (too generic)
  - ❌ `$entity`

### Expected vs Actual Pattern (RULE-VN-003)

- **Use in assertions**: `$this->assertSame($expected, $actual)`
- **For collections**: `$expectedItems`, `$actualItems`
- **Exception**: Use domain-specific names when more descriptive

## Testing Strategies

### Use Data Providers for Multiple Similar Test Cases (RULE-TS-001)

- **When to use**: Testing the same logic with 3 or more different inputs/outputs
- **Implementation**: Use Generator with yield and descriptive keys
- **Example**:

```php
#[DataProvider('addressStatusProvider')]
public function testValidateAddressWithVariousStatuses(string $status, bool $expected): void
{
    // test implementation
}

public static function addressStatusProvider(): Generator
{
    yield 'empty status' => ['', false];
    yield 'valid status' => ['checked', true];
    yield 'invalid status' => ['unknown', false];
}
```

### Use Inline Data for Single Test Cases (RULE-TS-002)

- **When to use**: Testing unique scenarios or complex object setups
- **Rationale**: Improves readability for one-off tests
- **Example**:

```php
public function testComplexScenarioWithSpecificSetup(): void
{
    $complexObject = new ComplexObject();
    $complexObject->configure(['specific' => 'settings']);
    
    // test implementation
}
```

## Assertions

### Use Strict Assertions

- Use `assertSame()` instead of `assertEquals()` for value comparisons
- Use `assertCount()` instead of `assertEquals()` for collection counts
- Use type-specific assertions: `assertIsString()`, `assertIsArray()`, etc.

## Data Providers and Generators

### When to Use Data Providers

- When testing the same logic with different inputs
- When you have 3 or more similar test cases
- To avoid test method duplication

### Data Provider Naming Conventions (RULE-MN-002, RULE-MN-003)

- **Pattern**: camelCase with 'Provider' suffix
- **Visibility**: Must be `public static`
- **Include domain context**: Make names descriptive
- **Examples**:
  - ✅ `addressStatusProvider()`
  - ✅ `validationScenarioProvider()`
  - ✅ `amazonPayAccountIdProvider()`
  - ✅ `germanAddressFormatProvider()`
  - ❌ `AddressStatusProvider()` (wrong case)
  - ❌ `provideAddressStatus()` (wrong pattern)
  - ❌ `dataProvider()` (too generic)

### Generator Implementation

Use generators for memory efficiency when dealing with larger datasets:

```php
/**
 * Provides test data for address validation scenarios.
 * 
 * @return Generator<string, array{string, bool, string}>
 *         - string $address: The address to validate
 *         - bool $isValid: Whether the address should be considered valid
 *         - string $scenario: Description of the test scenario
 */
public static function addressValidationProvider(): Generator
{
    yield 'valid address' => ['Main St 123', true, 'Should validate correct address'];
    yield 'empty address' => ['', false, 'Should reject empty address'];
    
    // For larger datasets
    for ($i = 0; $i < 100; $i++) {
        yield "case {$i}" => [
            $this->generateTestData($i),
            $this->generateExpectedResult($i)
        ];
    }
}

#[DataProvider('addressValidationProvider')]
public function testWithProvidedData($address, $isValid, $scenario): void
{
    // Test implementation
}
```

### Helper Methods with Generators

```php
private function createEntitiesWithGenerator(int $count)
{
    for ($i = 1; $i <= $count; $i++) {
        yield EntityClass::create("id-{$i}");
    }
}
```

## Stubs and Mocks

### Distinguish Between Mocks and Stubs (RULE-TS-003)

- **Use createMock()**: Only when setting expectations with `expects()`
- **Use createStub()**: When no method call verification is needed

### Type Hint Mocks and Stubs (RULE-TS-004)

- **Format**: `ServiceInterface&MockObject`
- **Benefit**: Better IDE support and type safety

### When to Use Test Doubles

- **Always** use stubs/mocks for Shopware entities and services
- **Never** test Shopware's internal logic
- Focus on testing YOUR code's interaction with Shopware

### Stub Usage (Preferred)

Use stubs when you only need to provide canned responses:

```php
public function testWithStub(): void
{
    $customerAddress = $this->createStub(CustomerAddressEntity::class);
    $customerAddress->method('getId')->willReturn('test-id');
    
    // Test your code that uses the stub
}
```

### Mock Usage

Use mocks only when you need to verify method calls:

```php
public function testWithMock(): void
{
    $repository = $this->createMock(EntityRepository::class);
    $repository->expects($this->once())
        ->method('search')
        ->with($this->isInstanceOf(Criteria::class))
        ->willReturn($searchResult);
    
    // Test code that should call the mocked method
}
```

#### Testing Service Calls with Parameter Assertions

Mocks are particularly useful for verifying that your code calls services with the correct parameters. Use PHPUnit's constraint system to assert complex parameter values:

```php
public function testServiceCallsWithCorrectParameters(): void
{
    $addressValidator = $this->createMock(AddressValidatorService::class);
    
    // Assert exact parameter values
    $addressValidator->expects($this->once())
        ->method('validate')
        ->with(
            'Hauptstraße 1',     // street
            '12345',            // postal code
            'Berlin',           // city
            'DE'                // country
        )
        ->willReturn(new ValidationResult(true));
    
    $service = new AddressService($addressValidator);
    $service->processAddress('Hauptstraße 1', '12345', 'Berlin', 'DE');
}

public function testServiceCallsWithComplexParameterAssertions(): void
{
    $repository = $this->createMock(EntityRepository::class);
    
    // Use callback for complex parameter assertions
    $repository->expects($this->once())
        ->method('upsert')
        ->with($this->callback(function (array $data) {
            // Verify the structure and content of the data array
            $this->assertArrayHasKey('id', $data[0]);
            $this->assertArrayHasKey('status', $data[0]);
            $this->assertSame('validated', $data[0]['status']);
            $this->assertArrayHasKey('validatedAt', $data[0]);
            $this->assertInstanceOf(\DateTimeInterface::class, $data[0]['validatedAt']);
            
            return true; // Must return true for the constraint to pass
        }))
        ->willReturn(new EntityWrittenResult());
    
    $service = new ValidationService($repository);
    $service->markAsValidated('entity-id');
}

public function testMultipleServiceCallsInOrder(): void
{
    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    
    // Define expected call sequence
    $eventDispatcher->expects($this->exactly(3))
        ->method('dispatch')
        ->withConsecutive(
            [$this->isInstanceOf(AddressValidationStartedEvent::class)],
            [$this->isInstanceOf(AddressValidatedEvent::class)],
            [$this->isInstanceOf(AddressValidationCompletedEvent::class)]
        );
    
    $validator = new AddressValidator($eventDispatcher);
    $validator->validate($address);
}
```

#### Common Parameter Constraints

- `$this->equalTo($value)` - Exact value match (default when passing direct values)
- `$this->identicalTo($value)` - Strict comparison (===)
- `$this->isInstanceOf($className)` - Type checking
- `$this->isType('array')` - PHP type checking
- `$this->arrayHasKey('key')` - Array structure validation
- `$this->stringContains('substring')` - Partial string matching
- `$this->greaterThan($value)` - Numeric comparisons
- `$this->logicalAnd(...)` - Combine multiple constraints
- `$this->callback($callable)` - Custom validation logic

#### Verifying No Calls Were Made

```php
public function testServiceNotCalledUnderCertainConditions(): void
{
    $logger = $this->createMock(LoggerInterface::class);
    
    // Assert that error method is never called
    $logger->expects($this->never())
        ->method('error');
    
    $service = new ProcessingService($logger);
    $service->processValidData($validData); // Should not trigger error logging
}
```

## Code Reuse Patterns

### setUp() Method Usage (RULE-CR-001)

- **When to extract**: When 3+ tests need the same setup
- **Include**: Mock/stub creation, common entities
- **Exclude**: Test-specific data
- **Example**:

```php
protected function setUp(): void
{
    $this->addressCheckerMock = $this->createMock(AddressCheckerInterface::class);
    $this->customerRepositoryStub = $this->createStub(CustomerRepository::class);
}
```

### Factory Methods for Test Objects (RULE-CR-002)

- **Pattern**: `private function create{Entity}(params): Entity`
- **Location**: After all test methods, before data providers
- **Example**:

```php
private function createCustomerAddressEntity(
    string $id = 'test-id',
    string $street = 'Test Street 123'
): CustomerAddressEntity {
    $entity = new CustomerAddressEntity();
    $entity->setId($id);
    $entity->setStreet($street);
    return $entity;
}
```

### Helper Method Extraction (RULE-CR-003)

- **Pattern**: `private function setup{Operation}(): void`
- **When to use**: Same operation needed in 2+ tests
- **Grouping**: Related helpers together with comment header
- **Example**:

```php
// Validation setup helpers

private function setupValidationContext(string $salesChannelId): void
{
    $this->enderecoServiceMock->expects($this->once())
        ->method('fetchSalesChannelId')
        ->willReturn($salesChannelId);
}

private function setupValidationAllowed(): void
{
    $this->validatorMock->expects($this->once())
        ->method('canValidate')
        ->willReturn(true);
}
```

### Descriptive Helper Names (RULE-CR-004)

- **Good examples**:
  - `setupStorefrontContext()`
  - `createAddressWithExtension()`
  - `assertAddressHasValidExtension()`
- **Bad examples**:
  - `setup()`
  - `create()`
  - `helper()`

### Helper Method Documentation (RULE-DC-003)

```php
/**
 * Sets up the validation context with a valid sales channel.
 * 
 * Configures the EndercoService mock to return the provided sales channel ID
 * and marks the plugin as active for that channel.
 * 
 * @param string $salesChannelId The sales channel ID to use
 */
private function setupValidationContext(string $salesChannelId): void
{
    // implementation
}
```

## Type Hints and Declarations

### Test Method Return Types (RULE-TH-001)

- **Requirement**: All test methods must declare `void` return type
- **Applies to**: Test methods, setUp(), tearDown()
- **Example**:

```php
public function testSomething(): void
{
    // test implementation
}

protected function setUp(): void
{
    // setup code
}
```

### Data Provider Return Types (RULE-TH-002)

- **Requirement**: Specify complete Generator return type
- **Format**: `@return Generator<string, array{type1, type2, ...}>`
- **Example**:

```php
/**
 * Provides test data for address validation scenarios.
 * 
 * @return Generator<string, array{string, bool, string}>
 */
public static function validationDataProvider(): Generator
{
    yield 'valid address' => ['Main St 123', true, 'Should validate correct address'];
    yield 'empty address' => ['', false, 'Should reject empty address'];
}
```

### Property Type Declarations (RULE-TH-003)

- **Mock/Stub properties**: Use intersection types
- **Regular properties**: Use interface or class types
- **Example**:

```php
class SomeServiceTest extends TestCase
{
    private AddressCheckerInterface&MockObject $addressCheckerMock;
    private CustomerRepository&MockObject $customerRepositoryStub;
    private Context $context;
}
```

## Code Formatting

### PSR-12 Compliance (RULE-CF-001)

- **Indentation**: 4 spaces (no tabs)
- **Line length**: 120 characters maximum
- **Blank lines**: One between all class methods
- **Braces**: Opening brace on same line for methods

### Multiline Method Calls (RULE-CF-002)

```php
$this->serviceMock->expects($this->once())
    ->method('process')
    ->with(
        $this->identicalTo($entity),
        $this->callback(function ($param) {
            return $param instanceof ExpectedType;
        })
    )
    ->willReturn($expectedResult);
```

## Method Organization

### Test Class Structure (RULE-MO-001)

Follow this specific order within test classes:

```php
class SomeServiceTest extends TestCase
{
    // 1. Class properties (with type hints)
    private ServiceInterface&MockObject $serviceMock;
    private Context $context;
    
    // 2. setUp() method
    protected function setUp(): void
    {
        // initialization
    }
    
    // 3. tearDown() method (if needed)
    protected function tearDown(): void
    {
        // cleanup
    }
    
    // 4. Test methods (grouped by feature)
    
    // Tests for getPriority() method
    
    public function testGetPriorityReturnsExpectedValue(): void
    {
        // test
    }
    
    // Tests for validation logic
    
    public function testValidateAcceptsValidInput(): void
    {
        // test
    }
    
    // 5. Data provider methods
    
    public static function validationDataProvider(): Generator
    {
        // data
    }
    
    // 6. Private factory methods
    
    private function createService(): ServiceClass
    {
        // creation logic
    }
    
    // 7. Private helper methods
    
    private function setupValidationContext(): void
    {
        // setup logic
    }
}
```

### Test Grouping with Comments (RULE-MO-002)

- **Format**: `// Tests for {feature/method}`
- **Spacing**: One blank line before comment
- **Example**:

```php
// Tests for address validation

public function testValidateAcceptsValidAddress(): void { }

public function testValidateRejectsInvalidAddress(): void { }

// Tests for error handling

public function testHandlesNetworkErrors(): void { }
```

### Test Method Ordering (RULE-MO-003)

1. Simple state/getter tests (getPriority, getName, etc.)
2. Main functionality tests (core business logic)
3. Edge cases and error conditions
4. Integration-like tests (if any)

## Anti-Patterns to Avoid

Based on extensive research, these are the most damaging anti-patterns in PHPUnit testing:

### Testing Implementation Instead of Behavior

Tests should verify observable behavior, not internal implementation details. This makes tests more maintainable and less brittle when refactoring.

```php
// Bad: Testing internal implementation
public function testUserSetsProperties(): void
{
    $user = new User('John', 'john@example.com');
    
    $reflection = new ReflectionClass($user);
    $property = $reflection->getProperty('email');
    $property->setAccessible(true);
    
    $this->assertEquals('john@example.com', $property->getValue($user));
}

// Good: Testing observable behavior
public function testUserProvidesEmail(): void
{
    $user = new User('John', 'john@example.com');
    
    $this->assertEquals('john@example.com', $user->getEmail());
}
```

### Fragile Tests with Over-Specification

Avoid overly specific assertions that make tests brittle. Focus on the essential behavior rather than every detail.

```php
// Bad: Brittle test that breaks with any change
public function testOrderProcessing(): void
{
    $mock = $this->createMock(OrderRepository::class);
    $mock->expects($this->once())
         ->method('save')
         ->with($this->callback(function ($order) {
             return $order->getId() === 123
                 && $order->getStatus() === 'pending'
                 && $order->getCreatedAt()->format('Y-m-d') === date('Y-m-d');
         }));
}

// Good: Flexible test focusing on key behavior
public function testOrderProcessing(): void
{
    $repository = $this->createMock(OrderRepository::class);
    $repository->expects($this->once())
               ->method('save')
               ->with($this->isInstanceOf(Order::class));
    
    // If specific values are important, test them separately
    $service = new OrderService($repository);
    $order = $service->createOrder($data);
    
    $this->assertEquals('pending', $order->getStatus());
}
```

## Preventing Redundant Tests

### What NOT to Test

**Framework and Library Functionality**

Tests should not verify the correctness of framework or library code. This includes:
- Testing that framework methods work as documented
- Verifying library behavior that is already covered by the library's own tests
- Checking standard platform functionality

Focus only on testing how YOUR code uses these dependencies, not whether the dependencies themselves work correctly.

**Simple Pass-Through Code**

Code that merely delegates to framework functionality should be tested if it contains ANY additional logic, including:
- Parameter validation or transformation
- Error handling or exception wrapping
- Logging or event dispatching
- Conditional delegation based on input
- Any data manipulation before or after delegation

Use the mocking strategy described above to verify that the framework methods are called with the correct parameters when your code adds even minimal logic around the delegation.

**Simple Accessors Without Logic**

Accessors (getters/setters) should only be tested if they contain additional logic beyond simple property access. For accessors without additional logic, use the `@codeCoverageIgnore` annotation:

```php
/**
 * Gets the customer address ID.
 * 
 * @codeCoverageIgnore
 */
public function getCustomerAddressId(): string
{
    return $this->customerAddressId;
}

/**
 * Sets the validated status.
 * 
 * @codeCoverageIgnore
 */
public function setValidated(bool $validated): void
{
    $this->validated = $validated;
}
```

However, accessors MUST be tested when they include:
- Validation logic
- Data transformation
- Lazy loading
- Default value handling
- Type conversion
- State changes that affect other properties

Example of an accessor that requires testing:
```php
public function setEmail(string $email): void
{
    // This contains validation logic and must be tested
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email format');
    }
    $this->email = strtolower($email);
}
```

### What TO Test

1. **Business Logic**
   - Custom validation rules
   - Data transformation methods
   - Complex calculations

2. **Integration Points**
   - How your entities work with Shopware's collections
   - Custom event subscribers
   - API endpoint handlers

3. **Edge Cases and Error Conditions**
   - Null handling
   - Empty collections
   - Invalid data scenarios

4. **Shopware Class Inheritance Requirements**
   
   When extending Shopware classes (directly or indirectly), you MUST test for inheritance "requirements" that could cause runtime errors if not properly handled. Common issues include:
   
   **Automatic Unique Identifier Setting**
   
   Shopware entities require a unique identifier when added to collections. Test that your entities properly set this:
   
   ```php
   /**
    * Tests that entity can be added to Shopware collections without TypeError.
    * 
    * Shopware collections require entities to have a unique identifier set,
    * otherwise a runtime error occurs when adding to the collection.
    */
   public function testEntityCanBeAddedToCollection(): void
   {
       $entity = new CustomEntity();
       $entity->setId('test-id');
       
       $collection = new EntityCollection();
       
       // This would throw TypeError if uniqueIdentifier is not properly set
       $collection->add($entity);
       
       $this->assertCount(1, $collection);
   }
   ```
   
   **Required Method Implementations**
   
   Test that required abstract methods or interface contracts are properly implemented:
   
   ```php
   /**
    * Tests that entity properly implements required Shopware entity methods.
    * 
    * These methods are called by Shopware's internal systems and must
    * return expected values to prevent runtime errors.
    */
   public function testRequiredShopwareMethodsImplemented(): void
   {
       $entity = new CustomEntity();
       
       // Test getEntityName() returns non-empty string
       $this->assertNotEmpty($entity->getEntityName());
       
       // Test getUniqueIdentifier() returns value after ID is set
       $entity->setId('test-id');
       $this->assertSame('test-id', $entity->getUniqueIdentifier());
   }
   ```
   
   **Event Handling Requirements**
   
   When extending event subscribers, test that your implementation handles Shopware's event propagation correctly:
   
   ```php
   /**
    * Tests that subscriber properly handles Shopware event requirements.
    * 
    * Shopware may pass events with specific data structures that must
    * be handled correctly to prevent runtime errors.
    */
   public function testSubscriberHandlesShopwareEvents(): void
   {
       $subscriber = new CustomEventSubscriber();
       
       // Create event with minimum required Shopware data
       $event = new EntityWrittenEvent(
           $this->createMinimalEntityWrittenContainerEvent()
       );
       
       // Should not throw exceptions
       $subscriber->onEntityWritten($event);
   }
   ```
   
   Always examine parent Shopware classes for:
   - Required constructor parameters
   - Methods that must return specific types
   - Properties that must be initialized
   - Event data that must be present
   - Collection behavior requirements

### Consolidating Similar Tests

Instead of:
```php
public function testSingleExtension(): void { /* ... */ }
public function testTwoExtensions(): void { /* ... */ }
public function testThreeExtensions(): void { /* ... */ }
```

Use:
```php
#[DataProvider('extensionCountProvider')]
public function testCollectionWithMultipleExtensions(array $extensions, int $expected): void
{
    // Single test handling all cases
}
```

## Inline Comments

### When to Use Inline Comments

1. **Complex Business Logic**
   ```php
   // Calculate shipping costs including VAT for EU countries
   // but excluding VAT for non-EU countries
   $shippingCost = $this->calculateShipping($country);
   ```

2. **Non-Obvious Test Scenarios**
   ```php
   // This would throw TypeError if getUniqueIdentifier() returns null
   $collection->add($extension);
   ```

3. **Workarounds or Special Cases**
   ```php
   // In a true unit test, we would mock the collection, but this validates
   // the critical integration that caused the production issue
   ```

### When NOT to Use Inline Comments

- Obvious code: `// Create user` before `$user = new User()`
- Redundant explanations: `// Assert count is 1` before `$this->assertCount(1, $collection)`
- Code that self-documents through good naming

## Test Quality Checklist

Before committing tests, ensure:

- [ ] All test methods have descriptive PHPDoc comments
- [ ] Test methods follow the naming pattern: `test{What}{Condition}{ExpectedResult}`
- [ ] Variables follow naming conventions (mocks/stubs suffixed appropriately)
- [ ] Strict assertions (assertSame, assertCount) are used
- [ ] No redundant tests for framework functionality
- [ ] Data providers are used for parameterized tests (3+ similar cases)
- [ ] Data providers have descriptive names with 'Provider' suffix
- [ ] Generators are used for large datasets
- [ ] Shopware dependencies are stubbed/mocked appropriately
- [ ] Mocks are only used when behavior verification is needed
- [ ] All type hints are properly declared (including intersection types)
- [ ] Methods are organized in the correct order
- [ ] Test grouping comments are used for clarity
- [ ] No unnecessary inline comments
- [ ] `#[CoversClass]` attribute is present
- [ ] Tests focus on project-specific code
- [ ] Helper methods are properly documented
- [ ] Code follows PSR-12 formatting standards

## Running Unit Tests

### Basic Test Execution

The project uses PHPUnit 12 for testing. Tests can be executed using Composer scripts defined in `composer.json`:

```bash
# Run all tests (unit and integration)
composer test
# Or with composer run syntax
composer run test
# Or directly with the underlying command
phpunit

# Run only unit tests
composer test-unit
# Or with composer run syntax
composer run test-unit
# Or directly with the underlying command
phpunit --testsuite=unit

# Run only integration tests  
composer test-integration
# Or with composer run syntax
composer run test-integration
# Or directly with the underlying command
phpunit --testsuite=integration
```

### Customizing Script Commands

You can adapt the underlying commands for specific needs:

```bash
# Run tests with custom PHPUnit options
phpunit --stop-on-failure --verbose

# Run specific test file with coverage
php -dxdebug.mode=coverage vendor/bin/phpunit tests/Unit/Entity/CustomerAddress/EnderecoCustomerAddressExtensionEntityTest.php --coverage-html coverage

# Run PHPStan with different level
phpstan analyse -c phpstan.6.7.0.1.neon --level=8 src/
```

### Running Specific Tests

PHPUnit provides several options for running specific test files, classes, or methods:

```bash
# Run a specific test file
./vendor/bin/phpunit tests/Unit/Entity/CustomerAddress/EnderecoCustomerAddressExtensionEntityTest.php

# Run a specific test class (using fully qualified class name)
./vendor/bin/phpunit --filter EnderecoCustomerAddressExtensionEntityTest

# Run a specific test method
./vendor/bin/phpunit --filter testGetUniqueIdentifierReturnsAddressId

# Run tests matching a pattern
./vendor/bin/phpunit --filter "/::test.*AddressId/"
```

### Using Test Suites

The project defines two test suites in `phpunit.xml`:

```bash
# Run unit test suite (tests in tests/Unit directory)
./vendor/bin/phpunit --testsuite=unit

# Run integration test suite (tests in tests/Integration directory)
./vendor/bin/phpunit --testsuite=integration
```

### Common PHPUnit Options

Enhance test execution with these useful flags:

```bash
# Stop on first failure
./vendor/bin/phpunit --stop-on-failure

# Display verbose output
./vendor/bin/phpunit --verbose

# Show test execution time
./vendor/bin/phpunit --debug

# List available test suites
./vendor/bin/phpunit --list-suites

# List available tests
./vendor/bin/phpunit --list-tests

# Run tests in random order
./vendor/bin/phpunit --random-order

# Use specific configuration file
./vendor/bin/phpunit -c phpunit.xml
```

### Environment Configuration

The `phpunit.xml` configuration sets important environment variables:

```xml
<php>
    <ini name="error_reporting" value="-1"/>
    <ini name="memory_limit" value="-1"/>
    <env name="APP_ENV" value="test"/>
    <env name="SHELL_VERBOSITY" value="-1"/>
</php>
```

Override these in your test environment if needed:

```bash
# Run with different memory limit
php -d memory_limit=512M vendor/bin/phpunit

# Run with specific environment
APP_ENV=test ./vendor/bin/phpunit
```

### Troubleshooting Common Issues

**Class Not Found Errors**

Ensure the test bootstrap file exists and autoloading is configured:
```bash
# Regenerate autoload files
composer dump-autoload

# Check bootstrap path in phpunit.xml
cat phpunit.xml | grep bootstrap
```

**Memory Limit Exceeded**

For memory-intensive tests:
```bash
# Increase memory limit
php -d memory_limit=2G vendor/bin/phpunit

# Or use the composer script which sets unlimited memory
composer test
```

**Timeout Issues**

For long-running integration tests:
```bash
# Disable execution time limit
php -d max_execution_time=0 vendor/bin/phpunit
```

**Permission Errors**

Ensure test directories have proper permissions:
```bash
# Fix permissions for cache directory
chmod -R 777 tests/.phpunit.cache
```

## Code Coverage Analysis

### Prerequisites

Code coverage requires either Xdebug or PCOV. The project's composer scripts use Xdebug:

```bash
# Check if Xdebug is installed
php -m | grep xdebug

# Install Xdebug (if needed)
pecl install xdebug

# Alternative: Install PCOV for faster coverage
pecl install pcov
```

### Basic Coverage Generation

Use the provided Composer script for quick coverage reports:

```bash
# Generate text coverage report for unit tests
composer test-coverage-unit
# Or with composer run syntax
composer run test-coverage-unit
# Or directly with the underlying command
php -dxdebug.mode=coverage vendor/bin/phpunit --testsuite=unit --coverage-text
```

For more detailed coverage with HTML reports:

```bash
# Generate HTML coverage report
php -dxdebug.mode=coverage vendor/bin/phpunit --testsuite=unit --coverage-html tests/coverage

# Using PCOV (if installed)
php -dpcov.enabled=1 vendor/bin/phpunit --testsuite=unit --coverage-html tests/coverage
```

### Coverage Report Formats

PHPUnit supports multiple coverage formats:

```bash
# HTML report (most detailed, with line-by-line coverage)
./vendor/bin/phpunit --coverage-html tests/coverage/html

# Clover XML (for CI tools like SonarQube)
./vendor/bin/phpunit --coverage-clover tests/coverage/clover.xml

# Cobertura XML (for tools like Jenkins)
./vendor/bin/phpunit --coverage-cobertura tests/coverage/cobertura.xml

# Text summary (console output)
./vendor/bin/phpunit --coverage-text

# PHP format (for processing with PHP scripts)
./vendor/bin/phpunit --coverage-php tests/coverage/coverage.php
```

### Coverage Configuration in phpunit.xml

The project's `phpunit.xml` already excludes certain directories from coverage:

```xml
<source>
    <include>
        <directory>src</directory>
    </include>
    <exclude>
        <directory>src/Resources</directory>
        <directory>src/Migration</directory>
        <file>src/EnderecoShopware6Client.php</file>
    </exclude>
</source>
```

Add additional exclusions using attributes in your code:

```php
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\CoverageIgnore;

#[CoversNothing]
class MigrationTest extends TestCase { }

#[CoverageIgnore]
public function legacyMethod(): void { }
```

### Viewing HTML Coverage Reports

After generating HTML reports:

```bash
# macOS
open tests/coverage/html/index.html

# Linux
xdg-open tests/coverage/html/index.html

# Or start a local server
php -S localhost:8080 -t tests/coverage/html
```

The HTML report provides:
- File browser with coverage percentages
- Line-by-line coverage highlighting
- Method complexity metrics
- Coverage trends (when using --coverage-crap4j)

## Complete Example

Here's a comprehensive example demonstrating all the guidelines:

```php
<?php declare(strict_types=1);

namespace Endereco\Shopware6Client\Tests\Unit\Entity\CustomerAddress;

use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionCollection;
use Endereco\Shopware6Client\Entity\EnderecoAddressExtension\CustomerAddress\EnderecoCustomerAddressExtensionEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

#[CoversClass(EnderecoCustomerAddressExtensionCollection::class)]
class EnderecoCustomerAddressExtensionCollectionTest extends TestCase
{
    private EntityRepository&MockObject $customerRepositoryMock;
    private AddressCheckerInterface&MockObject $addressCheckerStub;
    
    protected function setUp(): void
    {
        $this->customerRepositoryMock = $this->createMock(EntityRepository::class);
        $this->addressCheckerStub = $this->createStub(AddressCheckerInterface::class);
    }
    
    // Tests for collection operations
    
    /**
     * Tests that manually created extensions can be added to collections without TypeError.
     * 
     * This test prevents the exact issue that caused production failures when
     * extensions without proper unique identifiers were added to collections.
     */
    public function testCollectionCanAddManuallyCreatedExtensions(): void
    {
        $extension = EnderecoCustomerAddressExtensionEntity::createWithDefaultValues('test-id');
        $collection = new EnderecoCustomerAddressExtensionCollection();
        
        // This would throw TypeError if getUniqueIdentifier() returns null
        $collection->add($extension);
        
        $this->assertCount(1, $collection);
        $this->assertSame($extension, $collection->first());
    }
    
    /**
     * Tests collection behavior with varying numbers of extensions.
     * 
     * Ensures extensions can be processed without unique identifier conflicts
     * regardless of collection size.
     */
    #[DataProvider('extensionCountProvider')]
    public function testCollectionOperationsWithExtensions(array $extensionIds, int $expectedCount): void
    {
        $extensions = array_map(
            fn(string $id) => $this->createExtensionEntity($id),
            $extensionIds
        );
        
        $collection = new EnderecoCustomerAddressExtensionCollection();
        foreach ($extensions as $extension) {
            $collection->add($extension);
        }
        
        $this->assertCount($expectedCount, $collection);
    }
    
    // Tests for repository integration
    
    /**
     * Tests that the repository is called with correct search criteria.
     * 
     * Verifies that our service passes the appropriate filters when
     * searching for address extensions.
     */
    public function testRepositorySearchWithCorrectCriteria(): void
    {
        $this->customerRepositoryMock->expects($this->once())
            ->method('search')
            ->with($this->callback(function ($criteria) {
                $this->assertInstanceOf(Criteria::class, $criteria);
                $this->assertCount(1, $criteria->getFilters());
                
                return true;
            }))
            ->willReturn($this->createSearchResult());
        
        $service = new AddressExtensionService($this->customerRepositoryMock);
        $service->findByCustomerId('customer-123');
    }
    
    /**
     * Provides test data for collection operations with varying extension counts.
     * 
     * @return Generator<string, array{array<string>, int}>
     */
    public static function extensionCountProvider(): Generator
    {
        yield 'single extension' => [['addr-1'], 1];
        yield 'multiple extensions' => [['addr-1', 'addr-2', 'addr-3'], 3];
        yield 'many extensions' => [range(1, 10), 10];
    }
    
    /**
     * Creates a test extension entity with default values.
     * 
     * @param string $id The unique identifier for the extension
     * @return EnderecoCustomerAddressExtensionEntity
     */
    private function createExtensionEntity(string $id): EnderecoCustomerAddressExtensionEntity
    {
        return EnderecoCustomerAddressExtensionEntity::createWithDefaultValues($id);
    }
    
    /**
     * Creates a mock search result for repository tests.
     * 
     * @return EntitySearchResult
     */
    private function createSearchResult(): EntitySearchResult
    {
        return new EntitySearchResult(
            'customer_address',
            0,
            new EntityCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }
}
```
