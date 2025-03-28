Feature:
  I want to make sure that the system is allow to access the details of a measurement

  Scenario: As an owner, I can access to edit measurements of my account
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "pastico" in "hotel" with id "container" exists
    Given measurement for container "container" from user "user1" with id "111" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/measurements/edit/111"
    Then the response status should be 200

  Scenario: As an owner, I can't access to edit measurements of other accounts
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "other" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "other" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "other" exists
    Given container for "pastico" in "hotel" with id "container" exists
    Given measurement for container "container" from user "user1" with id "111" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/measurements/edit/111"
    Then the response status should be 403

  Scenario: As an user, I can access to edit my own measurements
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "pastico" in "hotel" with id "container" exists
    Given measurement for container "container" from user "user1" with id "111" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/measurements/edit/111"
    Then the response status should be 200

  Scenario: As an user, I can't access to edit measurements of other users of my account
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" with id "user1" of account "mycompany" exists
    Given user "user2@dediez.software" with role "ROLE_USER" with id "user2" of account "mycompany" exists
    Given waste "Plastico" with id "plastico" exists
    Given property "Hotel" with id "hotel" of account "mycompany" exists
    Given container for "pastico" in "hotel" with id "container" exists
    Given measurement for container "container" from user "user1" with id "111" exists
    Given measurement for container "container" from user "user2" with id "222" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/measurements/edit/222"
    Then the response status should be 403