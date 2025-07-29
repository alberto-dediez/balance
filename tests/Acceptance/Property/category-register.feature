Feature:
  I want to make sure that the API allow to register a category

  Scenario: As an admin, I can access to register new categories
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/categories/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't register new categories
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/categories/register"
    Then the response status should be 403

  Scenario: As an admin, I can register new categories
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/categories/register"
    When I submit a form with "name:1 Estrella" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Categoría creada correctamente"