Feature:
  I want to make sure that the system is allow to remove a property

  Scenario: As an admin, I can access to remove properties
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given property "Hotel" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/properties/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado la propiedad"

  Scenario: As normal user I can't remove properties
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    Given property "Hotel" with id "333" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/properties/remove/333"
    Then the response status should be 403

  Scenario: As an owner, I can access to remove properties of my account
    Given account "MyCompany" with id "666" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
    Given property "Hotel" with id "333" of account "666" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/properties/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado la propiedad"

  Scenario: As an owner, I can't access to remove properties of other accounts
    Given account "MyCompany" with id "666" exists
    Given account "OtherCompany" with id "111" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
    Given property "Hotel" with id "333" of account "111" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/properties/remove/333"
    Then the response status should be 403