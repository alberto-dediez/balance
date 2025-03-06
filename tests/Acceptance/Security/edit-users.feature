Feature:
  I want to make sure that the system is allow to access the details of an user

  Scenario: As an admin, I can access to edit users
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/users/edit/333"
    Then the response status should be 200
    Then I see a form

  Scenario: As normal user I can't edit users
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    Given user "user2@dediez.software" with id "333" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/users/edit/333"
    Then the response status should be 403

  Scenario: As an admin, I can grant a new role to an user
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user2@dediez.software" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/users/edit/333"
    When I submit a form with "roles:[1,2]" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Concedido acceso como"

  Scenario: As an admin, I can grant a new role to an user
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user2@dediez.software" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/users/edit/333"
    When I submit a form with "roles:[1,2]" and button "Guardar y salir"
    Then I am redirected to "/es/admin/users" 