Feature:
    I want to make sure the system allows removing a Cartel Doggy Bag

    Scenario: As an owner, I can remove my Doggy Bag sign
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given doggy bag sign "Cartel puerta" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag-signs/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el cartel Cartel puerta"

    Scenario: As an owner, I cannot remove a sign from another account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given doggy bag sign "Cartel ajeno" with id "444" of account "othercompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag-signs/remove/444"
        Then the response status should be 403

    Scenario: As a normal user, I cannot remove a Doggy Bag sign
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given doggy bag sign "Cartel puerta" with id "333" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/doggy-bag-signs/remove/333"
        Then the response status should be 403
