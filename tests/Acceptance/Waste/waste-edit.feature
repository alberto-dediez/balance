Feature:
  I want to make sure that the system is allow to access the details of a waste

  Scenario: As an admin, I can access to edit wastes of my account
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given waste "Madera" with id "madera" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/wastes/edit/madera"
    When I submit a form with "name:Madera|density:50|color:#333333" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Tipología actualizada"

  Scenario: As an owner, I can't access to edit wastes
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given waste "Madera" with id "madera" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/admin/wastes/edit/madera"
    Then the response status should be 403