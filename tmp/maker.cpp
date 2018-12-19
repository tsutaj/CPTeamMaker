// PHP 版が完成しなかったのでそれまでの間に合わせプログラム

// 基本的な方針
// ・チームごとの評価指標は「レートの二乗和平均」
//   (強い人と弱い人のレートを単純に足したものは、その中間を 2 人用意したものに比べてどうかと言われると微妙なところがある)
// ・全体の割り当ての評価指標は「チームごとの評価指標の分散」
//   (偏りが出ないのが一番いいからね)
// ・所属が x 個かぶると、全体の割り当ての評価指標が (x+1) 倍される
//   (かなり大きなペナルティになるのでこれのおかげであまりかぶらないけど、オーバーフローが少し心配かも。浮動小数型のほうがいいかも？)

#include <iostream>
#include <cstdio>
#include <vector>
#include <string>
#include <queue>
#include <algorithm>
#include <random>
using namespace std;

// [lb, ub] の閉区間内の値をランダムに返す構造体
// #include <random> しよう

struct Rand {
public:
    Rand() = default;
    Rand(std::mt19937::result_type seed) : eng(seed) {}
    int NextInt(int lb, int ub) {
        return std::uniform_int_distribution<int>{lb, ub}(eng);
    }
    long long int NextLong(long long int lb, long long int ub) {
        return std::uniform_int_distribution<long long int>{lb, ub}(eng);
    }
    double NextDouble(double lb, double ub) {
        return std::uniform_real_distribution<double>{lb, ub}(eng);
    }
private:
    std::mt19937 eng{std::random_device{}()};
};

// 3 人チームを仮定 (本来ならば可変にすべきだけど、仮のスクリプトなので・・・)
const int NUM_OF_TEAM_MEMBER = 3;
// 焼きなましのステップ数 (適当に設定)
const int NUM_OF_ANNEALING_STEP = 5 * 100 * 100;
// 焼きなましの状態遷移で使用する温度
const double startTemp = 100000000000.0;
const double endTemp   = 1000.0;
// 所属なし
const string NONE_AFFIL = "none";

struct UserInfo {
    string user_name;
    long long int rating;
    string affiliation;
    UserInfo() {}
    UserInfo(string name, long long int value, string affil) :
        user_name(name), rating(value), affiliation(affil) {}
};

// チームの評価指標
pair<long long int, int> evaluateTeam(vector<UserInfo> team) {
    long long int res = 0, num_of_members = team.size();
    for(auto member : team) {
        // レートの二乗和平均
        res += member.rating * member.rating;
    }

    // 所属被りを検出
    int num_of_dbl = 0;
    for(int i=0; i<num_of_members; i++) {
        for(int j=i+1; j<num_of_members; j++) {
            if(team[i].affiliation == NONE_AFFIL) continue;
            if(team[j].affiliation == NONE_AFFIL) continue;

            if(team[i].affiliation == team[j].affiliation) {
                num_of_dbl++;
            }
        }
    }

    res /= num_of_members;
    return make_pair(res, num_of_dbl);
}

// 全体の評価指標
long long int evaluateWhole(vector< vector<UserInfo> > teams) {
    long long int eval_sum = 0, num_of_teams = teams.size();
    int dbl_sum = 0;
    vector<long long int> evals;
    for(auto team : teams) {
        long long int team_eval_res; int num_of_dbl;
        tie(team_eval_res, num_of_dbl) = evaluateTeam(team);
        eval_sum += team_eval_res;
        dbl_sum += num_of_dbl;
        evals.push_back(team_eval_res);
    }

    long long int mean = eval_sum / num_of_teams;

    // 分散を取る (小さいほうが良い)
    long long int res = 0;
    for(auto team_eval_res : evals) {
        long long int diff = mean - team_eval_res;
        res += diff * diff;
    }
    return res * (dbl_sum + 1);
}

void dump_teams_evaluation(vector< vector<UserInfo> > teams) {
    int num_of_teams = teams.size();
    for(int i=0; i<num_of_teams; i++) {
        long long int team_eval_res; int num_of_dbl;
        tie(team_eval_res, num_of_dbl) = evaluateTeam(teams[i]);
        fprintf(stderr, "%c team #%02d (point = %7lld):", (num_of_dbl > 0 ? '#' : ' '), i+1, team_eval_res);
        for(auto member : teams[i]) {
            fprintf(stderr, " [ %s (%s) ]", member.user_name.c_str(), member.affiliation.c_str());
        }
        fprintf(stderr, "\n");
    }
    fprintf(stderr, "# total = %15lld\n\n", evaluateWhole(teams));
}

using State = vector< vector<UserInfo> >;

int main(int argc, char** argv) {
    // ユーザー情報はファイルで受け取る
    if(argc < 2 or argc > 3) {
        fprintf(stderr, "Usage: %s [users-file] [<seed>]\n", argv[0]);
        fprintf(stderr, "default seed = 114,514\n");
        return 1;
    }

    FILE *fp = fopen(argv[1], "r");
    if(fp == NULL) {
        fprintf(stderr, "Error: cannot open %s.\n", argv[1]);
        return 1;
    }

    int seed = 114514;
    if(argc == 3) {
        seed = atoi(argv[2]);
    }
    Rand rnd(seed);

    // ユーザー情報について
    vector<UserInfo> users;
    char name[128], affil[128]; long long int rating;
    while( fscanf(fp, " %s %lld %s", name, &rating, affil) != EOF ) {
        fprintf(stderr, "# debug: [%s] [%lld] [%s]\n", name, rating, affil);
        users.emplace_back(string(name), rating, affil);
    }

    // 初期のチーム編成を決める
    int num_of_users = users.size();
    int div = num_of_users / NUM_OF_TEAM_MEMBER;
    int mod = num_of_users % NUM_OF_TEAM_MEMBER;

    int current_idx = 0;
    State initial_teams;

    vector<int> idx_row, idx_col;
    for(int i=0; i<div; i++) {
        initial_teams.push_back(vector<UserInfo>());
        for(int j=0; j<NUM_OF_TEAM_MEMBER + (i < mod); j++) {
            idx_row.push_back(i);
            idx_col.push_back(j);
            initial_teams.back().push_back(users[current_idx++]);
        }
    }

    // 初期状態を出力
    fprintf(stderr, "--- initial team assignments ---\n");
    dump_teams_evaluation(initial_teams);

    State current_teams = initial_teams;
    State best_teams = initial_teams;
    long long int current_score = evaluateWhole(current_teams);
    long long int best_score = current_score;

    for(int step=0; step<NUM_OF_ANNEALING_STEP; step++) {
        int u = rnd.NextInt(0, num_of_users - 1);
        int v = rnd.NextInt(0, num_of_users - 1);
        if(u == v) continue;

        State next_teams = current_teams;
        swap(next_teams[idx_row[u]][idx_col[u]], next_teams[idx_row[v]][idx_col[v]]);
        
        // 近傍選択もっと速く出来るんだけど、まぁそれは後で
        long long int next_score = evaluateWhole(next_teams);

        double temp = startTemp + (endTemp - startTemp) * step / NUM_OF_ANNEALING_STEP;
        double prob = exp((current_score - next_score) / temp);
        bool accept = prob > rnd.NextDouble(0, 1);

        if(accept) {
            // 新しいものを受理
            current_teams = next_teams;
            current_score = next_score;

            // スコアは小さいほど良い
            if(next_score < best_score) {
                best_teams = next_teams;
                best_score = next_score;
            }
        }
    }

    fprintf(stderr, "--- final team assignments ---\n");
    dump_teams_evaluation(best_teams);
    return 0;
}