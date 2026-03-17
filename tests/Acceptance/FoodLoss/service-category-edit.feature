Feature:
  I want to make sure that the system is allow to access the details of a service category

  Scenario: As an admin, I can access to edit service categories of my account
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given service category "Service category 1" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/service-categories/edit/333"
    When I submit a form with "name:Service category" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Categoría de servicio actualizada con éxito"

  Scenario: As an owner, I can't access to edit service categories
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given service category "Service category 1" with id "333" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/admin/service-categories/edit/333"
    Then the response status should be 403