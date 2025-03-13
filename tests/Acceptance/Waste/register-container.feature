Feature:
  I want to make sure that the software allow to register containers

  Scenario: As an owner, I can access to register new containers
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/containers/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't register new containers
    Given user "owner@dediez.software" with role "ROLE_OWNER" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/containers/register"
    Then the response status should be 403

  Scenario: As an owner, I can register new containers
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given waste "Madera" with id "madera" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/containers/register"
    Then the response status should be 200
    When I submit a form with "wasteId:madera|propertyId:hotel" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Contenedor creado correctamente"