Feature:
    Ability to list carteles Doggy Bag

    Scenario: As an owner, I can access the list of my Doggy Bag signs
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given doggy bag sign "Cartel puerta" with id "1111" of account "mycompany" exists
        Given doggy bag sign "Cartel carta" with id "2222" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag-signs"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As a normal user, I can access the list of Doggy Bag signs
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given doggy bag sign "Cartel puerta" with id "1111" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/doggy-bag-signs"
        Then the response status should be 200

    Scenario: As an admin, I cannot access the list of Doggy Bag signs
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/doggy-bag-signs"
        Then the response status should be 403
