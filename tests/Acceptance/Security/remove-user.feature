Feature:
  I want to make sure that the system is allow to remove an user

  Scenario: As an admin, I can access to remove users
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with id "333" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/admin/users/remove/333"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado al usuario"

  Scenario: As normal user I can't remove users
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given user "user1@dediez.software" with role "ROLE_USER" exists
    Given user "user2@dediez.software" with id "333" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/admin/users/remove/333"
    Then the response status should be 403

  Scenario: As owner I can remove users of my account
      Given account "MyCompany" with id "666" exists
      Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists  
      Given user "user1@dediez.software" with id "333" of account "666" exists
      Given user "user2@dediez.software" with id "4444" exists
      When I am logged as "owner@dediez.software"
      When I go to "/es/users/remove/333"
      When I follow redirect
      Then the response status should be 200

  Scenario: As owner I can't remove users of other accounts
      Given account "MyCompany" with id "666" exists
      Given account "OtherCompany" with id "44" exists
      Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists  
      Given user "user1@dediez.software" with id "333" of account "666" exists
      Given user "user2@dediez.software" with id "4444" of account "44" exists
      When I am logged as "owner@dediez.software"
      When I go to "/es/users/remove/4444"
      Then the response status should be 404