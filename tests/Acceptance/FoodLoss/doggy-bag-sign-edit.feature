Feature:
    I want to make sure the system allows editing a Cartel Doggy Bag

    Scenario: As an owner, I can access the edit form for my sign
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given doggy bag sign "Cartel puerta" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag-signs/edit/333"
        Then the response status should be 200
        Then I see a form

    Scenario: As an owner, I cannot edit a sign from another account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given doggy bag sign "Cartel ajeno" with id "444" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag-signs/edit/444"
        Then the response status should be 403

    Scenario: As a normal user, I cannot edit a Doggy Bag sign
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given doggy bag sign "Cartel puerta" with id "333" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/doggy-bag-signs/edit/333"
        Then the response status should be 403
