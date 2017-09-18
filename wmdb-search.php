<!-- Emily Van Laarhoven and Marissa Okoli, CS304 HW4 -->

<!DOCTYPE html>
<html>
<head>
  <meta charset = "utf-8">
  <meta name=author content="Emily Van Laarhoven, Marissa Okoli">
  <meta name=description content="hw4 php wmdb">
  <title>WMDB Search</title>
  <link href="https://fonts.googleapis.com/css?family=Raleway:500|Slabo+27px" rel="stylesheet">
  <link rel="stylesheet" href="hw4.css">
</head>
<body>
	<header>
		<img src="wellesley-logo.jpeg" alt="Wellesley College Logo" />
		<h1>Wellesley Movie Database Search Tool</h1>
	</header>
	<main>
		<h2 class="intro">Welcome to the WMDB</h2>
		<p class="intro">This is the Wellesley Movie Database. Enter a search item below to see what 
		information we have on movies, actors and directors.</p>
		<div class="form-box">
			<h3>Search the WMDB</h3>
			<form id="search_form" action=""> <!-- can also use php self -->
				<p><select required name="tables"></p>
        			<option value="">Search by:</option>
					<option>both</option>
        			<option>actor</option>
					<option>movie</option>
				</select>
				<p><label>Search:<input required name="sought"></label></p>
				<p><input type="submit"></p>
      		</form>
		</div>
		<div class="listing">
			<?php

/* database preliminaries */
require_once("/home/cs304/public_html/php/DB-functions.php");
require_once('wmdb-dsn.inc');
$dbh = db_connect($wmdb_dsn);
$self = $_SERVER['PHP_SELF'];
$bool_actor = true;
$bool_movie = true;


/* sql statements for detailed views */
    $sql_actor = "SELECT * FROM person WHERE nm=?";
    $sql_filmography = "SELECT * FROM movie, credit WHERE movie.tt=credit.tt and credit.nm=?";
    $sql_movie = "SELECT * FROM movie WHERE tt=?";
    $sql_director = "SELECT * FROM person WHERE nm=(SELECT director FROM movie WHERE tt=?)";
    $sql_cast = "SELECT * FROM person,credit WHERE credit.nm=person.nm AND credit.tt=?";

/* check to see that both table dropdown and search term are nonempty (required by form) */
if (isset($_GET['tables']) and isset($_GET['sought'])) {

/* create prepared queries for SQL using the pattern given by search term */
$tab = $_GET['tables'];
$sought_name = htmlspecialchars($_GET['sought']);
$search = "%".htmlspecialchars($sought_name)."%";
echo "<h2 id='sr'>The Search Results for \"$sought_name\"</h2>";
echo "<div>";

/* create sql statements */
$sql_names = "SELECT *, (SELECT COUNT(*) FROM person WHERE person.name LIKE ?) AS total FROM person WHERE person.name LIKE ?";
$sql_movies = "SELECT *, (SELECT COUNT(*) FROM movie WHERE movie.title LIKE ?) AS total FROM movie WHERE movie.title LIKE ?";

/* create prepared queries */
$pq_names = prepared_query($dbh,$sql_names,array($search,$search));
$pq_movies = prepared_query($dbh,$sql_movies,array($search,$search));

/* if statement executes names query and displays actor names */
 if($tab=='actor' or $tab=='both') {
    while($row = $pq_names -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
      $count_names = htmlspecialchars($row['total']);
      $result_name = htmlspecialchars($row['name']);
      $nm = htmlspecialchars($row['nm']);
      if ($bool_actor) {
           echo "<p><strong>Number of actors resulting from your search: $count_names</strong></p>";
           $bool_actor = false;
       }
/* deals with case of only one result */
       if ($count_names==1) {
        $pq_actor = prepared_query($dbh,$sql_actor,array($nm));
        $pq_filmography = prepared_query($dbh,$sql_filmography,array($nm));
        while($row = $pq_actor -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $actor_name = htmlspecialchars($row['name']);
            $actor_bday = htmlspecialchars($row['birthdate']);
            echo "<h3>$actor_name</h3>";
            echo "<p><em>Born on $actor_bday</em></p>";
         }
         echo "<h4>Filmography:</h4>";
         echo "<ul>";
        while($row = $pq_filmography -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $film = htmlspecialchars($row['title']);
            $tt = htmlspecialchars($row['tt']);
            echo "<li><a href='$self?tt=$tt'>$film</a></li>";
         }
         echo "</ul>";
       } else {
            echo "<p><a href='$self?nm=$nm'>$result_name</a></p>";
        }
      }      
 }

/* if statement executes movies query and displays movie titles */
   if($tab=='movie' or $tab=='both') {
      while($row = $pq_movies -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
      $count_movies = htmlspecialchars($row['total']);
      $result_title = htmlspecialchars($row['title']);
      $rel_yr = htmlspecialchars($row['release']);
      $tt = htmlspecialchars($row['tt']);
      if ($bool_movie) {
         echo "<p style='padding-top:20px;'><strong>Number of movies resulting from your search: $count_movies</strong></p>";
         $bool_movie = false;
      }
/* deals with case of only one result */
      if ($count_movies==1) {
           $pq_movie = prepared_query($dbh,$sql_movie,array($tt));
           $pq_director = prepared_query($dbh,$sql_director,array($tt));
           $pq_cast = prepared_query($dbh,$sql_cast,array($tt));
           while($row = $pq_movie -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
              $title = htmlspecialchars($row['title']);
              $rel_yr = htmlspecialchars($row['release']);
              echo "<h3>$title($rel_yr)</h3>";
           }
          while($row = $pq_director -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
              $movie_director = htmlspecialchars($row['name']);
              $nm=htmlspecialchars($row['nm']);
              echo "<p><em>Directed by <a href='$self?nm=$nm'>$movie_director</a></em></p>";
           }
           echo "<h4>Cast Members:</h4>";
           echo "<ul>";
         while($row = $pq_cast -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
              $movie_castmember = htmlspecialchars($row['name']);
              $nm = htmlspecialchars($row['nm']);
              echo "<li><a href='$self?nm=$nm'>$movie_castmember</a></li>";
          }
          echo "</ul>";
          } else {
      echo "<p><a href='$self?tt=$tt'>$result_title($rel_yr)</a></p>";
   }
   }
}
}

/* use cases where user clicks on above links to either actor or movie */
if (isset($_GET['nm'])) {
    $nm = htmlspecialchars($_GET['nm']);
    $pq_actor = prepared_query($dbh,$sql_actor,array($nm));
    $pq_filmography = prepared_query($dbh,$sql_filmography,array($nm));
    while($row = $pq_actor -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $actor_name = htmlspecialchars($row['name']);
        $actor_bday = htmlspecialchars($row['birthdate']);
        echo "<h3>$actor_name</h3>";
        echo "<p><em>Born on $actor_bday</em></p>";
    }
    echo "<h4>Filmography:</h4>";
    echo "<ul>";
    while($row = $pq_filmography -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $film = htmlspecialchars($row['title']);
        $tt = htmlspecialchars($row['tt']);
        echo "<li><a href='$self?tt=$tt'>$film</a></li>";
    }
    echo "</ul>";
}
if (isset($_GET['tt'])) {
     $tt = htmlspecialchars($_GET['tt']);
     $pq_movie = prepared_query($dbh,$sql_movie,array($tt));
     $pq_director = prepared_query($dbh,$sql_director,array($tt));
     $pq_cast = prepared_query($dbh,$sql_cast,array($tt));
     while($row = $pq_movie -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $title = htmlspecialchars($row['title']);
         $rel_yr = htmlspecialchars($row['release']);
         echo "<h3>$title($rel_yr)</h3>";
     }
     while($row = $pq_director -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $movie_director = htmlspecialchars($row['name']);
         $nm= htmlspecialchars($row['nm']);
         echo "<p><em>Directed by <a href='$self?nm=$nm'>$movie_director</a></em></p>";
     }
     echo "<h4>Cast Members:</h4>";
     echo "<ul>";
     while($row = $pq_cast -> fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $movie_castmember = htmlspecialchars($row['name']);
         $nm = htmlspecialchars($row['nm']);
         echo "<li><a href='$self?nm=$nm'>$movie_castmember</a></li>";
     }
     echo "</ul>";
     echo "</div>";
}


?>
			<ul>
			</ul>
		</div>
	</main>
</body>
</html>
