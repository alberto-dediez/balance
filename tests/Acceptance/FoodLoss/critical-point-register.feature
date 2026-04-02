Feature:
  I want to make sure that the software allow to register a critical point

  Scenario: As an owner, I can access to register new critical points
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't register new critical points
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/critical-points/register"
    Then the response status should be 403

  Scenario: As an owner, I can register new critical points for my account
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "My Property" with id "myprop" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/register"
    When I submit a form with "name:Test CP|propertyId:myprop|supplyChainStage:0" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Punto crítico creado correctamente: Test CP"

  Scenario: As an owner, the register form has a service category field
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/register"
    Then the response status should be 200
    Then I see a form field "serviceCategoryIds"

  Scenario: As an owner, I can register a critical point with service categories
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given property "My Property" with id "myprop" of account "mycompany" exists
    Given service category "Category A" with id "cat1" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/critical-points/register"
    When I submit a form with "name:Test CP|propertyId:myprop|supplyChainStage:0|serviceCategoryIds:[cat1]" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Punto crítico creado correctamente: Test CP"
